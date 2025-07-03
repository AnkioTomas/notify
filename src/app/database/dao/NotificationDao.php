<?php

namespace app\database\dao;

use app\database\model\ChannelModel;
use app\database\model\NotificationModel;
use nova\plugin\orm\object\Dao;

class NotificationDao extends Dao
{
    function getUnread(): array
    {
        return $this->select()->where(["is_read" => false])->commit();
    }

    function post($title,$content)
    {
        $model = new NotificationModel();
        $model->title = $title;
        $model->content = $content;
        $model->is_read = false;
        $model->t = time();
    }
}