<?php

declare(strict_types=1);

namespace app\controller\index;

use app\database\dao\AppDao;
use app\database\dao\NotificationDao;
use app\database\model\NotificationModel;
use app\utils\WechatException;
use app\utils\WorkWechatApp;

use function nova\framework\config;

use nova\framework\http\Response;
use nova\framework\json\Json;
use nova\framework\json\JsonDecodeException;
use nova\framework\route\Controller;

class Main extends Controller
{
    /**
     * 发布通知（支持直接header映射和ntfy兼容）
     * POST /{channel}
     */
    public function publish(string $channel): Response
    {
        $type = $this->request->get("type", 'ntfy');

        $app = AppDao::getInstance()->shortName($channel);
        if (empty($app)) {
            return $this->publishErrorResponse($type, 404, 'Unknow channel', 404);
        }

        $headers = $this->request->getHeaders();

        $authorization = $headers['Authorization'] ?? $this->request->arg('authorization') ?? null;

        if (strlen($authorization) <= 6 || config('authorization') !== $authorization) {
            return $this->publishErrorResponse($type, 403, 'Unauthorized', 403);
        }
        // 校验授权

        // Title	通知标题	X-Title: 警报
        //Priority	优先级 (1-5 或 min, low, default, high, urgent)	X-Priority: 5
        //Tags	标签/表情符号（用逗号分隔）	X-Tags: warning,skull
        //Delay	延迟发送（例如 30min 或 时间戳）	X-Delay: 10am
        //Actions	交互按钮（JSON 格式）	X-Actions: view, Open, [https://google.com](https://google.com)
        //Click	点击通知时跳转的 URL	X-Click: [https://example.com](https://example.com)
        //Attach	附件 URL	X-Attach: [https://example.com/file.jpg](https://example.com/file.jpg)

        $model = match ($type) {
            'ntfy' => $this->getFromNtfy($app->id),
            'form' => $this->getFromForm($app->id),
            'dingding' => $this->getFromDingding($app->id),
            'feishu' => $this->getFromFeishu($app->id),
            'wechat' => $this->getFromWechat($app->id),
            default => null
        };

        if ($model === null) {
            return $this->publishErrorResponse($type, 400, 'Unknow model', 400);
        }

        $model = NotificationDao::getInstance()->post($model);

        // 发布到微信

        $toUser = config('work_wechat.to_user');
        if (!empty($toUser) && $app->secret !== '' && $app->agent_id !== '') {
            try {
                WorkWechatApp::getInstance($app)->sendText($model->toWechat(), $toUser);
            } catch (WechatException $e) {
                // 企微上游 API 常为 HTTP 200 + errcode≠0；这里对各 type 继续用语义化 body（见 publishErrorResponse）
                return $this->publishErrorResponse($type, 200, $e->getMessage(), $e->getCode() ?: 500);
            }
        }

        return $this->publishSuccessResponse($type, $model);
    }

    /**
     * 按 type 输出各 webhook 方言，避免钉钉/飞书等只认 errcode、code 的客户端误判。
     *
     * - dingding/wechat：对齐企微/钉钉机器人的 errcode/errmsg（业务失败时 HTTP 仍可为 200）
     * - feishu：常见 code/msg/data
     * - ntfy：简化版消息事件字段
     * - form：保持原统一 JSON（通用 HTTP API）
     */
    private function publishSuccessResponse(string $type, NotificationModel $model): Response
    {
        return match ($type) {
            'dingding', 'wechat' => Response::asJson(['errcode' => 0, 'errmsg' => 'ok'], 200),
            'feishu'             => Response::asJson(['code' => 0, 'msg' => 'success', 'data' => new \stdClass()], 200),
            default               => Response::asJson(['msg' => 'Success', 'code' => 200, 'data' => $model], 200),
        };
    }

    /**
     * @param int $httpCode        HTTP 层状态（鉴权失败等仍可 403/400）
     * @param int $bizOrVendorCode 映射到方言里的业务码（如 errcode）；为 0 时按 HTTP 推导
     */
    private function publishErrorResponse(
        string $type,
        int $httpCode,
        string $message,
        int $bizOrVendorCode = 0
    ): Response {
        $code = $bizOrVendorCode > 0 ? $bizOrVendorCode : ($httpCode > 0 ? $httpCode : 400);

        return match ($type) {
            'dingding', 'wechat' => Response::asJson([
                'errcode' => $code,
                'errmsg'  => $message,
            ], $httpCode),
            'feishu' => Response::asJson([
                'code' => $code,
                'msg'  => $message,
                'data' => new \stdClass(),
            ], $httpCode),
            default => Response::asJson(['msg' => $message, 'code' => $code], $httpCode),
        };
    }

    private function splitActions(string $actions): array
    {
        $items = explode(';', trim($actions, ';'));

        $a = [];
        foreach ($items as $item) {
            if (empty($item)) {
                continue;
            }
            [$name, $url] = explode(',', $item);
            $a[trim($name)] = trim($url);
        }
        return $a;

    }

    public function getFromNtfy(int $app): ?NotificationModel
    {
        $headers = $this->request->getHeaders();

        $title = rawurldecode($headers['X-Title'] ?? '');

        $priority = $headers['X-Priority'] ?? 'info';
        $actions = rawurldecode($headers['X-Actions'] ?? '');
        $message = $this->request->raw() ?? '';

        if (!empty($actions)) {
            $actions = $this->splitActions($actions);
        } else {
            $actions = [];
        }

        $model = new NotificationModel([
            'app' => $app,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'actions' => $actions,
            't' => time(),
        ]);
        return $model;

    }

    public function getFromForm(int $app): ?NotificationModel
    {
        $title = rawurldecode($this->request->post('title') ?? '');
        $message = $this->request->post('message') ?? $this->request->post('content') ?? '';
        $priority = $this->request->post('priority') ?? $this->selectPriority($title . $message);
        $actionsStr = rawurldecode($this->request->post('actions') ?? '');
        if (!empty($actionsStr)) {
            $actions = $this->splitActions($actionsStr);
        } else {
            [$title_, $message_, $actions] = $this->splitTitleBody($message);
        }

        $model = new NotificationModel([
            'app' => $app,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'actions' => $actions,
            't' => time(),
        ]);
        return $model;
    }

    public function getFromDingDing(int $app): ?NotificationModel
    {
        $rawHttp = $this->request->raw();
        if ($rawHttp === '') {
            return null;
        }
        try {
            $data = Json::decode($rawHttp, true);
        } catch (JsonDecodeException) {
            return null;
        }
        if (empty($data['msgtype'])) {
            return null;
        }
        $title = $message = '';
        $priority = 'info';
        $actions = [];
        if (isset($data['text']['content'])) {
            $rawText = $data['text']['content'];
            [$title, $message, $actions] = $this->splitTitleBody($rawText);
        } elseif (isset($data['markdown'])) {
            $title = $data['markdown']['title'] ?? '';
            $message = $data['markdown']['text'] ?? '';

        }

        if (empty($title)) {
            return null;
        }

        return new NotificationModel([
            'app' => $app,
            'title' => $title,
            'message' => $message,
            'priority' => $this->selectPriority($title . $message),
            'actions' => $actions,
            't' => time(),
        ]);
    }

    /**
     * 解析企业微信机器人 webhook JSON → {@see NotificationModel}。
     * 仅 text / markdown：正文串第一行为 title，余下为 message（与 {@see splitTitleBody} 一致）。
     */
    public function getFromWechat(int $app): ?NotificationModel
    {
        $rawHttp = $this->request->raw();
        if ($rawHttp === '') {
            return null;
        }
        try {
            $data = Json::decode($rawHttp, true);
        } catch (JsonDecodeException) {
            return null;
        }
        if (empty($data['msgtype'])) {
            return null;
        }

        if (empty($data['text']['content']) && empty($data['markdown']['content'])) {
            return null;
        }

        $type = $data['msgtype'];
        $blob = match ($type) {
            'text' => $data['text']['content'],
            'markdown' => $data['markdown']['content'],
            default => null,
        };
        if ($blob === null || trim($blob) === '') {
            return null;
        }
        [$title, $message, $actions] = $this->splitTitleBody($blob);

        return new NotificationModel([
            'app' => $app,
            'title' => $title,
            'message' => $message,
            'priority' => $this->selectPriority($blob),
            'actions' => $actions,
            't' => time(),
            'short_url' => '',
        ]);
    }

    public function getFromFeishu(int $app): ?NotificationModel
    {
        $raw = $this->request->raw();
        if (empty($raw)) {
            return null;
        }
        try {
            $data = Json::decode($raw, true);
        } catch (JsonDecodeException $e) {
            return null;
        }
        if (empty($data['msg_type'])) {
            return null;
        }
        $title = $message = '';
        $priority = 'info';
        $actions = [];
        if (isset($data['content']['text'])) {
            $rawText = $data['content']['text'];
            [$title, $message, $actions] = $this->splitTitleBody($rawText);
        } elseif (isset($data['content']['post']['zh-CN'])) {
            $title = $data['content']['post']['zh-CN']['title'] ?? '';
            $messageItems = [];

            foreach ($data['content']['post']['zh-CN']['content'] as $value) {
                if ($value['tag'] === 'text') {
                    $messageItems[] = $value['text'];
                } elseif ($value['tag'] === 'a') {
                    $actions[$value['text']] = $value['href'];
                }
            }

            $message = join("\n", $messageItems);
        }

        if (empty($title)) {
            return null;
        }

        return new NotificationModel([
            'app' => $app,
            'title' => $title,
            'message' => $message,
            'priority' => $this->selectPriority($title . $message),
            'actions' => $actions,
            't' => time(),
        ]);
    }

    /** 第一行 → title，其后（可空）→ message */
    private function splitTitleBody(string $raw): array
    {
        // 1. 标准化换行符并拆分标题与正文
        $s = str_replace(["\r\n", "\r"], "\n", $raw);
        $p = strpos($s, "\n");

        $body = '';

        if ($p === false) {
            $title = trim($s);
        } else {
            $title = trim(substr($s, 0, $p));
            $body = trim(substr($s, $p + 1));
        }

        // 2. 提取链接
        // 这个正则可以匹配 http/https 以及常见的域名格式
        $pattern = '/https?:\/\/[^\s\x{4e00}-\x{9fa5}]+/u';
        $links = [];

        if (preg_match_all($pattern, $raw, $matches)) {
            // 去重处理，防止同一链接出现多次
            $uniqueLinks = array_unique($matches[0]);
            $i = 1;
            foreach ($uniqueLinks as $url) {
                $links["链接$i"] = $url;
                $i++;
            }
        }

        $actions = $this->request->get('actions');
        if ($actions != null) {
            $actions  = $this->splitActions(rawurldecode($actions));
        } else {
            $actions = $links;
        }

        // 3. 返回数组，第三个参数是链接对象（关联数组）
        return [
            $title,
            $body,
            $actions // 强制转换为对象，确保输出 JSON 时是 {} 而不是 []
        ];
    }

    private function selectPriority($raw): string
    {
        if (empty($raw)) {
            return 'info';
        }

        // 定义特征词库及其对应的正则（按优先级排序）
        // 这里的顺序很重要：先匹配 error，最后才是 info
        $map = [
            'error' => '/(error|fail|exception|fatal|critical|错误|失败|异常|崩溃|拒绝)/i',
            'warning' => '/(warning|warn|caution|alert|警告|注意|异常提醒|预警)/i',
            'success' => '/(success|ok|passed|complete|done|成功|通过|完成)/i',
            'info' => '/(info|notice|debug|msg|log|信息|通知|日志)/i',
        ];

        // 按顺序进行正则扫描，一旦命中立即返回
        foreach ($map as $priority => $pattern) {
            if (preg_match($pattern, $raw)) {
                return $priority;
            }
        }

        // 如果包含特定的状态码逻辑（可选）
        // 例如：HTTP 状态码或业务错误码
        if (preg_match('/\b(4\d{2}|5\d{2})\b/', $raw)) {
            return 'error';
        }

        return 'info'; // 兜底类型
    }
}
