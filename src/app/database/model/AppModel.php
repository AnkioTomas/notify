<?php

declare(strict_types=1);

namespace app\database\model;

use nova\plugin\orm\object\Model;

class AppModel extends Model
{
    public string $name = ""; // 渠道名称
    public string $short_name = "";
    public string $agent_id = ""; //应用id
}
