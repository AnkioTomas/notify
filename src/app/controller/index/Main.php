<?php

declare(strict_types=1);

namespace app\controller\index;

use app\Application;
use app\database\dao\AppDao;
use app\database\dao\NotificationDao;
use app\database\model\NotificationModel;

use app\utils\Parsedown;
use app\utils\WorkWechatApp;

use function nova\framework\config;

use nova\framework\http\Response;
use nova\framework\route\Controller;
use nova\plugin\tpl\ViewResponse;

class Main extends Controller
{
    /**
     * priority -> M3 角色色（直接对接 base.css 的 utility class）。
     * 4 种 priority 映射到 4 种 M3 角色色，无需任何自定义颜色。
     */
    private const array PRIORITY_META = [
        'info'    => ['label' => '信息', 'icon' => 'info',         'class' => 'primary'],
        'warning' => ['label' => '警告', 'icon' => 'warning',      'class' => 'tertiary'],
        'error'   => ['label' => '错误', 'icon' => 'error',        'class' => 'error'],
        'success' => ['label' => '成功', 'icon' => 'check_circle', 'class' => 'secondary'],
    ];
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

        $title = rawurldecode($headers['X-Title'] ?? '');

        $priority = $headers['X-Priority'] ?? 'info';
        $actions = rawurldecode($headers['X-Actions'] ?? '');
        $message = $this->request->raw() ?? '';
        $items = explode(';', trim($actions, ';'));

        $a  = [];
        foreach ($items as $item) {
            if (empty($item)) {
                continue;
            }
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
        if (!empty($toUser) && $app->secret !== '' && $app->agent_id !== '') {
            WorkWechatApp::getInstance($app)->sendText($model->toWechat(), $toUser);
        }

        return Response::asJson(["msg" => "Success", "code" => 200, "data" => $model], 200);

    }

    /**
     * 通知详情（"查看原文"页）。
     * GET /{short}
     */
    public function short(string $short): Response
    {
        $model = NotificationDao::getInstance()->getByShortUrl($short);

        if ($model === null) {
            return Response::asHtml($this->renderNotFound(), [], 404);
        }

        $app  = AppDao::getInstance()->id($model->app);
        $meta = self::PRIORITY_META[$model->priority] ?? self::PRIORITY_META['info'];

        $bodyHtml = $model->message !== ''
            ? (new Parsedown())->setSafeMode(false)->text($model->message)
            : '';

        $view = new ViewResponse();

        return $view->asTpl('', [
            'title'         => ($model->title !== '' ? $model->title : '通知详情') . ' - ' . Application::SYSTEM_NAME,
            'notifyTitle'   => $model->title !== '' ? $model->title : '（无标题）',
            'channelName'   => $app?->name ?? '未知频道',
            'bodyHtml'      => $bodyHtml,
            'priorityLabel' => $meta['label'],
            'priorityIcon'  => $meta['icon'],
            'priorityClass' => $meta['class'],
            'time'          => $model->t > 0 ? date('Y-m-d H:i:s', $model->t) : '',
            'actions'       => $model->actions,
        ]);
    }

    /**
     * 渲染 404 页（独立页，避免依赖模板编译与 pjax 那套）。
     */
    private function renderNotFound(): string
    {
        $title = '通知不存在 - ' . Application::SYSTEM_NAME;

        return <<<HTML
<!doctype html>
<html lang="zh-CN" class="mdui-theme-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>{$title}</title>
    <link rel="stylesheet" href="/static/bundle?file=framework/libs/mdui.css,framework/base.css&type=css"/>
    <style>
        .nf-card    { max-width: 480px; padding: 2.5rem 2rem; }
        .nf-icon    { font-size: 4rem; }
    </style>
</head>
<body class="bg-background text-on-background min-h-screen d-flex items-center justify-center">
    <mdui-card variant="elevated" class="nf-card text-center">
        <mdui-icon name="error_outline" class="nf-icon text-error"></mdui-icon>
        <h2 class="headline-medium mt-3 mb-2">通知不存在</h2>
        <p class="body-medium text-on-surface-variant mb-4">链接已失效或通知已被删除。</p>
        <mdui-button variant="filled" href="/">返回首页</mdui-button>
    </mdui-card>
    <script src="/static/bundle?file=framework/libs/mdui.global.min.js&type=js"></script>
</body>
</html>
HTML;
    }
}
