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
    public string $token      = ""; // 企业微信回调 Token
    public string $aes_key    = ""; // 企业微信回调 EncodingAESKey

    public function getUnique(): array
    {
        return ['agent_id'];
    }

    public function getSchemaVersion(): int
    {
        return 3;
    }

    public function getUpgradeSql(): array
    {
        return [
            "1_2" => [
                "ALTER TABLE `{table}` ADD COLUMN `secret` VARCHAR(255) NOT NULL DEFAULT ''",
            ],
            "2_3" => [
                "ALTER TABLE `{table}` ADD COLUMN `token` VARCHAR(128) NOT NULL DEFAULT ''",
                "ALTER TABLE `{table}` ADD COLUMN `aes_key` VARCHAR(64) NOT NULL DEFAULT ''",
            ],
        ];
    }
}
