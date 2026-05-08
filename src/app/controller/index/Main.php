<?php

declare(strict_types=1);

namespace app\controller\index;

use app\database\dao\AppDao;
use app\database\dao\NotificationDao;
use app\database\model\NotificationModel;

use app\utils\WorkWechatApp;

use function nova\framework\config;

use nova\framework\http\Response;
use nova\framework\route\Controller;

class Main extends Controller
{
    /**
     * 发布通知（支持直接header映射和ntfy兼容）
     * POST /{channel}
     */
    public function publish(string $channel): Response
    {
        $app = AppDao::getInstance()->shortName($channel);
        if (empty($app)) {
            return Response::asJson(["msg" => "Unknow channel", "code" => 404,], 404);
        }

        $headers = $this->request->getHeaders();

        $authorization = $headers['Authorization'] ?? null;

        if (config('authorization') !== $authorization) {
            return Response::asJson(["msg" => "Unauthorized", "code" => 403,], 403);

        }
        // 校验授权

        $title = $headers['X-Title'] ?? '';

        $priority = $headers['X-Priority'] ?? 'info';
        $actions = $headers['X-Actions'] ?? '';
        $message = $this->request->raw() ?? '';
        $items = explode(';', trim($actions, ';'));

        $a  = [];
        foreach ($items as $item) {
            [$name,$url] = explode(',', $item);
            $a[trim($name)] = trim($url);
        }
        $actions = $a;

        // Title	通知标题	X-Title: 警报
        //Priority	优先级 (1-5 或 min, low, default, high, urgent)	X-Priority: 5
        //Tags	标签/表情符号（用逗号分隔）	X-Tags: warning,skull
        //Delay	延迟发送（例如 30min 或 时间戳）	X-Delay: 10am
        //Actions	交互按钮（JSON 格式）	X-Actions: view, Open, [https://google.com](https://google.com)
        //Click	点击通知时跳转的 URL	X-Click: [https://example.com](https://example.com)
        //Attach	附件 URL	X-Attach: [https://example.com/file.jpg](https://example.com/file.jpg)

        $model = new NotificationModel([
            'app' => $app->id,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'actions' => $actions,
            't' => time(),
        ]);

        $model = NotificationDao::getInstance()->post($model);

        // 发布到微信

        $toUser = config('work_wechat.to_user');
        if ($toUser !== null) {

            $wechat = $model->toWechat();
            WorkWechatApp::getInstance()->sendText($app->agent_id, $wechat, $toUser);
        }

        return Response::asJson(["msg" => "Success", "code" => 200, "data" => $model], 200);

    }
}
