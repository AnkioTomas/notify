<?php

declare(strict_types=1);

namespace app\controller\index;

use app\Application;
use app\database\dao\AppDao;
use app\database\dao\NotificationDao;
use app\utils\Parsedown;
use nova\framework\http\Response;
use nova\framework\route\Controller;
use nova\plugin\tpl\ViewResponse;

/**
 * 通知「查看原文」公开页：与 nova/plugin/tpl/error 相同的全页壳 + PJAX 片段机制。
 */
class Short extends Controller
{
    /**
     * GET /{short}
     */
    public function detail(string $short): Response
    {
        $model = NotificationDao::getInstance()->getByShortUrl($short);
        if ($model === null) {
            return Response::asRedirect('/404');
        }

        $app = AppDao::getInstance()->id($model->app);

        $parsedown = new Parsedown();
        $parsedown->setBreaksEnabled(true);

        $bodyHtml = $model->message !== ''
            ? $parsedown->text($model->message)
            : '';

        $actions = $model->actions ?? [];
        if ($actions !== []) {
            $json = json_encode($actions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
            $actionsEncoded = $json !== false ? rawurlencode($json) : '';
        } else {
            $actionsEncoded = '';
        }

        $pageTitle = ($model->title !== '' ? $model->title : '通知详情') . ' - ' . Application::SYSTEM_NAME;

        $view = new ViewResponse();
        // 非 PJAX 套 layout（子页进 <template id="page">）；PJAX 只出片段。页面始终由本 action 渲染。
        $view->init($this->request->isPjax() ? '' : 'layout', [
            'title' => $pageTitle,
        ]);

        return $view->asTpl('detail', [
            'title'           => $pageTitle,
            'heading'         => $model->title !== '' ? $parsedown->text($model->title) : '（无标题）',
            'channel'         => $app?->name ?? '未知频道',
            'priority'        => $model->priority,
            'time'            => $model->t > 0 ? date('Y-m-d H:i:s', $model->t) : '',
            'bodyHtml'        => $bodyHtml,
            'actionsEncoded'  => $actionsEncoded,
        ]);
    }
}
