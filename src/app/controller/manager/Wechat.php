<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\database\dao\AppDao;
use app\utils\WechatException;
use app\utils\Work_wechatConfig;
use app\utils\WorkWechatAdmin;
use nova\framework\http\Response;
use nova\plugin\cookie\Session;
use nova\plugin\login\controller\BaseAPIController;

class Wechat extends BaseAPIController
{
    /**
     * 同一路径承载查询与保存：GET 取配置，POST 保存配置。
     */
    public function config(): Response
    {
        $cfg = Work_wechatConfig::getInstance();
        if ($this->request->isPost()) {
            $cfg->corpid = trim((string)$this->request->post('corpid', ''));
            $cfg->to_user = trim((string)$this->request->post('to_user', ''));
            $cfg->token = trim((string)$this->request->post('token', ''));
            $cfg->aes_key = trim((string)$this->request->post('aes_key', ''));

            return Response::asJson([
                'code' => 200,
                'msg' => '保存成功',
            ]);
        }

        return Response::asJson([
            'code' => 200,
            'data' => [
                'corpid'  => $cfg->corpid,
                'to_user' => $cfg->to_user,
                'token'   => $cfg->token,
                'aes_key' => $cfg->aes_key,
            ],
        ]);
    }

    /**
     * 申请企业微信管理后台登录二维码。
     */
    public function qrBegin(): Response
    {
        self::releaseSession();
        try {
            return Response::asJson(['code' => 200, 'data' => WorkWechatAdmin::begin()]);
        } catch (WechatException $e) {
            return Response::asJson(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 长轮询扫码状态。LOGGED_IN 已登录；NEED_CAPTCHA 需填短信验证码。
     */
    public function qrPoll(): Response
    {
        self::releaseSession();
        try {
            $data = WorkWechatAdmin::poll((string)$this->request->post('key', ''), (string)$this->request->post('status', ''));

            return Response::asJson(['code' => 200, 'data' => $data]);
        } catch (WechatException $e) {
            return Response::asJson(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 提交异地登录短信验证码。
     */
    public function qrCaptcha(): Response
    {
        self::releaseSession();
        try {
            WorkWechatAdmin::confirmCaptcha(
                (string)$this->request->post('key', ''),
                (string)$this->request->post('tl_key', ''),
                trim((string)$this->request->post('captcha', ''))
            );

            return Response::asJson(['code' => 200, 'data' => ['status' => 'LOGGED_IN']]);
        } catch (WechatException $e) {
            return Response::asJson(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    public function qrEnd(): Response
    {
        WorkWechatAdmin::end((string)$this->request->post('key', ''));

        return Response::asJson(['code' => 200]);
    }

    /**
     * 检查管理后台会话是否仍可用；前端有缓存 key 时先打这个，避免每次扫码。
     */
    public function session(): Response
    {
        $key = (string)$this->request->post('key', '');
        $ok = WorkWechatAdmin::alive($key);

        return Response::asJson([
            'code' => 200,
            'data' => ['alive' => $ok],
        ]);
    }

    /**
     * 同步渠道到企业微信自建应用：已有则更新名称/图标/回调，没有则创建并回写 agent_id，再触发 Secret。
     */
    public function sync(): Response
    {
        $key = (string)$this->request->post('key', '');
        $id = (int)$this->request->post('id', 0);
        $app = AppDao::getInstance()->id($id);
        if ($app === null) {
            return Response::asJson(['code' => 404, 'msg' => '渠道不存在']);
        }
        if ($key === '' || !WorkWechatAdmin::alive($key)) {
            return Response::asJson(['code' => 401, 'msg' => '企业微信管理后台会话已失效，请重新扫码']);
        }

        self::releaseSession();

        try {
            $name = $app->name !== '' ? $app->name : '通知渠道';
            $remote = WorkWechatAdmin::findApp($key, $app->agent_id, $name);
            $created = false;
            if ($remote === null) {
                $remote = WorkWechatAdmin::createApp($key, $name, $app->logo);
                $created = true;
            } else {
                try {
                    WorkWechatAdmin::updateApp($key, $remote['app_id'], $name, $app->logo);
                } catch (WechatException $e) {
                    // 名称/图标更新失败不阻断回调与 Secret
                    $profileWarn = $e->getMessage();
                }
            }

            $cfg = Work_wechatConfig::getInstance();
            $corpId = (string)($remote['corp_id'] ?? '');
            if ($corpId !== '' && $cfg->corpid !== $corpId) {
                $cfg->corpid = $corpId;
            }

            $app->agent_id = (string)$remote['agent_id'];
            AppDao::getInstance()->updateModel($app);

            [$token, $aesKey] = $this->ensureCallbackSecrets();
            $warning = isset($profileWarn) ? ('资料未更新成功：' . $profileWarn) : '';
            try {
                WorkWechatAdmin::saveCallback(
                    $key,
                    $remote['app_id'],
                    $this->request->getBasicAddress() . '/hook',
                    $token,
                    $aesKey
                );
            } catch (WechatException $e) {
                $warning = trim($warning . ' 回调未配置成功：' . $e->getMessage());
            }

            try {
                WorkWechatAdmin::sendSecret($key, $remote['app_id']);
            } catch (WechatException $e) {
                $warning = trim($warning . ' Secret 未触发：' . $e->getMessage());
            }
        } catch (WechatException $e) {
            return Response::asJson([
                'code' => self::isSessionError($e) ? 401 : 500,
                'msg'  => $e->getMessage(),
            ]);
        }

        $action = $created ? '已新建企业微信应用' : '已同步企业微信应用配置';
        $msg = $warning !== '' ? ($action . '，但' . $warning) : ($action . '，请到企业微信客户端查看并复制 Secret');

        return Response::asJson([
            'code' => 200,
            'msg'  => $msg,
            'data' => [
                'agent_id' => $app->agent_id,
                'created'  => $created,
            ],
        ]);
    }

    private static function isSessionError(WechatException $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, '会话') || str_contains($msg, '扫码') || str_contains($msg, '登录');
    }

    /**
     * Token/AESKey 全局共用：已有则沿用，否则生成。变更会让旧应用回调失效。
     *
     * @return array{0: string, 1: string}
     */
    private function ensureCallbackSecrets(): array
    {
        $cfg = Work_wechatConfig::getInstance();
        if ($cfg->token === '' || strlen($cfg->aes_key) !== 43) {
            $cfg->token = bin2hex(random_bytes(8));
            $cfg->aes_key = substr(rtrim(base64_encode(random_bytes(32)), '='), 0, 43);
        }

        return [$cfg->token, $cfg->aes_key];
    }

    /** 长耗时企微请求前释放 session 写锁 */
    private static function releaseSession(): void
    {
        Session::getInstance()->close();
    }
}
