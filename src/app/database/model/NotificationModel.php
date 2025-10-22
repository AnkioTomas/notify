<?php

declare(strict_types=1);

namespace app\database\model;

use nova\plugin\orm\object\Model;

class NotificationModel extends Model
{
    public int     $channel         = 0;         // 频道名称
    public string  $title           = "";        // 消息标题
    public string  $message         = "";        // 消息内容（纯文本 / Markdown）
    public string  $type            = "default"; // 消息类型：default, info, warning, error, success
    public string  $actionLeftUrl   = "";        // 左侧按钮 URL
    public string  $actionLeftText  = "";        // 左侧按钮文本
    public string  $actionRightUrl  = "";        // 右侧按钮 URL
    public string  $actionRightText = "";        // 右侧按钮文本
    public bool    $is_read         = false;     // 是否已读
    public int     $t               = 0;         // 发布时间戳（秒）

    public function getNoEscape(): array
    {
        return ["message"];
    }

}
