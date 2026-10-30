<?php

declare(strict_types=1);

namespace app\database\model;

use nova\plugin\orm\object\Model;

class AppModel extends Model
{
    public string $name       = ""; // 渠道显示名
    public string $short_name = ""; // URL 短标识
    public string $agent_id   = ""; // 企业微信应用 AgentId（同步后回写，创建时为空）
    public string $secret     = ""; // 企业微信应用 Secret
    public string $token      = ""; // 企业微信回调 Token
    public string $logo       = ""; // 应用图标（data URL 或纯 base64），同步到企微

    public function getUnique(): array
    {
        return ['short_name'];
    }

    public function getSchemaVersion(): int
    {
        return 5;
    }

    public function getUpgradeSql(): array
    {
        return [
            "1_2" => [
                "ALTER TABLE {table} ADD COLUMN `secret` VARCHAR(255) NOT NULL DEFAULT ''",
            ],
            "2_3" => [
                "ALTER TABLE {table} ADD COLUMN `token` VARCHAR(128) NOT NULL DEFAULT ''",
                "ALTER TABLE {table} ADD COLUMN `aes_key` VARCHAR(64) NOT NULL DEFAULT ''",
            ],
            // 单条 ALTER 原子执行：已有重复 short_name 时整体失败，不会留下半升级的表
            "3_4" => [
                "ALTER TABLE {table} MODIFY `short_name` VARCHAR(191) NOT NULL DEFAULT '', ADD UNIQUE KEY `short_name` (`short_name`), DROP INDEX `agent_id`",
            ],
            "4_5" => [
                "ALTER TABLE {table} ADD COLUMN `logo` MEDIUMTEXT NULL",
            ],
        ];
    }
}
