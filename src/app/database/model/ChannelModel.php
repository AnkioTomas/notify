<?php

namespace app\database\model;

use nova\plugin\orm\object\Model;

class ChannelModel extends Model
{
    public string $channel = ""; // 渠道名称
    public string $token = "";
}