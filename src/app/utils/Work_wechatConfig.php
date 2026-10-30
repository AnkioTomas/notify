<?php

declare(strict_types=1);

namespace app\utils;

use nova\framework\core\ConfigObject;

/**
 * 企业微信全局配置。
 *
 * 类名带下划线是为了让 ConfigObject 推导出节点名 work_wechat（与现有 config.php 对齐）。
 */
class Work_wechatConfig extends ConfigObject
{
    /** @var string 兼容历史字段，业务未使用 */
    public string $default = 'notify';

    public string $corpid = '';

    public string $to_user = '';

    public string $token = '';

    public string $aes_key = '';
}
