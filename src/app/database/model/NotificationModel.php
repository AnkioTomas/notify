<?php

declare(strict_types=1);

namespace app\database\model;

use nova\plugin\orm\object\Model;

class NotificationModel extends Model
{
    public string $title    = "";    // 消息标题
    public string $message  = "";    // 富文本信息（HTML / Markdown）
    public string $type     = "default"; // 通知类型：success、warning、error、default
    public bool   $is_read  = false; // 是否已读
    public string $actionLeftUrl   = ""; // 左侧操作按钮URL
    public string $actionLeftText  = ""; // 左侧操作按钮文本
    public string $actionRightUrl  = ""; // 右侧操作按钮URL
    public string $actionRightText = ""; // 右侧操作按钮文本
    public int $t = 0;//发布时间
}
