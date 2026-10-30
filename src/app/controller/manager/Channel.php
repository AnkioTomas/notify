<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\database\dao\AppDao;
use app\database\model\AppModel;
use app\utils\WechatException;
use app\utils\WorkWechatAdmin;
use nova\framework\http\Response;
use nova\plugin\cookie\Session;
use nova\plugin\login\controller\BaseAPIController;

class Channel extends BaseAPIController
{
    public function list(): Response
    {
        $where = [];

        $page = (int)$this->request->get("page", 1);
        $size = (int)$this->request->get("pageSize", 10);

        $result = AppDao::getInstance()->getAll([], $where, $page, $size, "id");
        $total = $result['total'];
        $rows = $result['data'] ?: [];

        return Response::asJson([
            'code'  => 200,
            'count' => $total,
            'data'  => $rows,
        ]);
    }

    public function del(): Response
    {
        $id = (int)$this->request->post("id", 0);
        $key = (string)$this->request->post("key", '');
        $dao = AppDao::getInstance();
        $app = $dao->id($id);
        if ($app === null) {
            return Response::asJson(['code' => 404, 'msg' => '渠道不存在']);
        }

        // 已绑定企业微信应用时，必须带扫码会话并先删远端，避免本地删了远端残留
        if ($app->agent_id !== '') {
            if ($key === '') {
                return Response::asJson(['code' => 401, 'msg' => '请先扫码登录企业微信管理后台']);
            }
            // 删远端要跑 curl，先放掉 session，别跟 sync 一样在 JSON 后再写缓存
            Session::getInstance()->close();
            try {
                $remote = WorkWechatAdmin::findApp($key, $app->agent_id);
                if ($remote !== null) {
                    WorkWechatAdmin::deleteApp($key, $remote['app_id'], $remote['agent_id']);
                }
            } catch (WechatException $e) {
                $msg = $e->getMessage();
                $code = (str_contains($msg, '会话') || str_contains($msg, '扫码') || str_contains($msg, '登录')) ? 401 : 500;

                return Response::asJson(['code' => $code, 'msg' => '企业微信应用删除失败：' . $msg]);
            }
        }

        $dao->remove($id);

        return Response::asJson([
            'code' => 200,
            'msg'  => '删除成功',
        ]);
    }

    public function edit(): Response
    {
        $app = new AppModel($_POST);
        $dao = AppDao::getInstance();

        $owner = $dao->shortName($app->short_name);
        if ($owner !== null && $owner->id !== $app->id) {
            return Response::asJson(['code' => 400, 'msg' => "短标识 {$app->short_name} 已被渠道「{$owner->name}」使用"]);
        }

        // agent_id 只允许同步回写；新建强制空，编辑保留库里的值防前端乱改
        if ($app->id > 0) {
            $old = $dao->id($app->id);
            $app->agent_id = $old?->agent_id ?? '';
            if ($app->logo === '' && $old !== null) {
                $app->logo = $old->logo;
            }
            $dao->updateModel($app);
        } else {
            $app->agent_id = '';
            $dao->insertModel($app);
        }
        return Response::asJson([
            'code' => 200,
            'msg' => '操作成功'
        ]);
    }
}
