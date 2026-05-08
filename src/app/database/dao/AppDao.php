<?php

declare(strict_types=1);

namespace app\database\dao;

use app\database\model\AppModel;
use nova\plugin\orm\object\Dao;

class AppDao extends Dao
{
    public function shortName(string $name): ?AppModel
    {
        return $this->find(null, ["short_name" => $name]);
    }

    public function id(int $id): ?AppModel
    {
        return $this->find(null, ["id" => $id]);
    }

    public function remove(int $id): void
    {
        $app = $this->id($id);
        if (!$app) {
            return;
        }
        $this->delete()->where(["id" => $id])->commit();
        NotificationDao::getInstance()->removeByApp($id);
    }
}
