<?php

namespace app\database\dao;

use app\database\model\NotificationModel;
use nova\plugin\orm\object\Dao;

class NotificationDao extends Dao
{
    function getUnread($since_ts): array
    {
        return $this->select()->where(["is_read" => false, "t > $since_ts"])->commit();

    }

    function post($title,$content): void
    {
        $model = new NotificationModel();
        $model->title = $title;
        $model->content = $content;
        $model->is_read = false;
        $model->t = time();
        $this->insertModel($model);
    }
}