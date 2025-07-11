<?php

declare(strict_types=1);

namespace app\database\model;

use nova\plugin\orm\object\Model;

class NotificationModel extends Model
{
    public string $title    = "";    // 消息标题
    public string $content  = "";    // 富文本内容（HTML / Markdown）
    public bool   $is_read  = false; // 是否已读

    public string $link = "";// 跳转链接

    public int $t = 0;//发布时间
}
