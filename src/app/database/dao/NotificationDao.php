<?php

declare(strict_types=1);

namespace app\database\dao;

use app\database\model\NotificationModel;
use nova\plugin\orm\object\Dao;

class NotificationDao extends Dao
{
    public function get($since_ts): array
    {
        return $this->select()->where([ "t > $since_ts"])->orderBy("id")->commit();
    }

    public function post($title, $message, $type = "default", $actionLeftUrl = "", $actionLeftText = "", $actionRightUrl = "", $actionRightText = ""): void
    {
        $model = new NotificationModel();
        $model->title = $title;
        $model->message = $message;
        $model->type = $type;
        $model->actionLeftUrl = $actionLeftUrl;
        $model->actionLeftText = $actionLeftText;
        $model->actionRightUrl = $actionRightUrl;
        $model->actionRightText = $actionRightText;
        $model->is_read = false;
        $model->t = time();
        $this->insertModel($model);
        if (rand(1, 100) == 1) {
            $this->deleteTimeOut();
        }
    }

    public function deleteTimeOut(): void
    {
        $this->delete()->where(["t < ".(time() - 3600 * 24 * 2)])->commit();
    }
}
