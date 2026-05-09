<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\database\dao\AppDao;
use app\database\dao\NotificationDao;
use app\utils\Parsedown;
use nova\framework\http\Response;

class Notification extends BaseController
{
    public function list(): Response
    {
        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('pageSize', 10);

        $appId = (int)$this->request->get('app_id', 0);

        $priority = trim((string)$this->request->get('priority', ''));
        $allowedPriority = ['info', 'warning', 'error', 'success'];
        if ($priority !== '' && !in_array($priority, $allowedPriority, true)) {
            $priority = '';
        }

        $result = NotificationDao::getInstance()->paginateLatest(
            $page,
            $pageSize,
            $appId > 0 ? $appId : null,
            $priority !== '' ? $priority : null
        );
        $appDao = AppDao::getInstance();
        $parsedown = (new Parsedown())->setSafeMode(false);

        $data = [];
        foreach ($result['data'] as $model) {
            $app = $appDao->id($model->app);
            $actions = $model->actions ?? [];
            if (!is_array($actions)) {
                $actions = [];
            }

            $messageHtml = $model->message !== ''
                ? $parsedown->text($model->message)
                : '';

            $data[] = [
                'id'         => $model->id,
                'short_url'  => $model->short_url,
                'title'      => $model->title,
                'message'    => $model->message,
                'messageHtml' => $messageHtml,
                'priority'   => $model->priority,
                'actions'    => $actions === [] ? new \stdClass() : $actions,
                'channel'    => $app?->name ?? '未知频道',
                'time'       => $model->t > 0 ? date('Y-m-d H:i:s', $model->t) : '',
                'detailHref' => '/' . $model->short_url,
            ];
        }

        return Response::asJson([
            'code'  => 200,
            'count' => $result['total'],
            'data'  => $data,
        ]);
    }
}
