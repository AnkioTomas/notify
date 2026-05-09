<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\database\dao\AppDao;
use app\database\dao\NotificationDao;
use app\database\model\NotificationModel;
use nova\framework\http\Response;

class Notification extends BaseController
{
    public function list(): Response
    {
        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('pageSize', 10);

        $result = NotificationDao::getInstance()->paginateLatest($page, $pageSize);
        $appDao = AppDao::getInstance();

        $data = [];
        foreach ($result['data'] as $model) {
            if (!$model instanceof NotificationModel) {
                continue;
            }
            $app = $appDao->id($model->app);
            $actions = $model->actions ?? [];
            if (!is_array($actions)) {
                $actions = [];
            }

            $data[] = [
                'id'         => $model->id,
                'short_url'  => $model->short_url,
                'title'      => $model->title,
                'message'    => $model->message,
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
