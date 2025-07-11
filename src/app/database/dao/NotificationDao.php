<?php

declare(strict_types=1);

namespace app\database\dao;

use app\database\model\NotificationModel;
use nova\plugin\orm\object\Dao;

class NotificationDao extends Dao
{
    public function getUnread($since_ts): array
    {
        return $this->select()->where(["is_read" => false, "t > $since_ts"])->commit();
    }

    public function post($title, $content, $link): void
    {
        $model = new NotificationModel();
        $model->title = $title;
        $model->content = $content;
        $model->link = $link;
        $model->is_read = false;
        $model->t = time();
        $this->insertModel($model);
        $this->deleteTimeOut();
    }

    public function deleteTimeOut(): void
    {
        $this->delete()->where(["t < ".(time() - 3600 * 365)])->commit();
    }
}
