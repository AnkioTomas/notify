<?php

declare(strict_types=1);

namespace app\database\model;

use nova\plugin\orm\object\Model;

class AppModel extends Model
{
    public string $name       = ""; // 渠道显示名
    public string $short_name = ""; // URL 短标识
    public string $agent_id   = ""; // 企业微信应用 AgentId
    public string $secret     = ""; // 企业微信应用 Secret

    public function getSchemaVersion(): int
    {
        return 2;
    }

    public function getUpgradeSql(): array
    {
        return [
            "1_2" => [
                "ALTER TABLE `{table}` ADD COLUMN `secret` VARCHAR(255) NOT NULL DEFAULT ''",
            ],
        ];
    }
}
