<?php

declare(strict_types=1);

namespace app\database\dao;

use app\database\model\NotificationModel;
use nova\framework\cache\Cache;
use nova\plugin\orm\exception\DbFieldError;
use nova\plugin\orm\object\Dao;

class NotificationDao extends Dao
{
    public function lastest(int $channel,int $since_ts): array
    {
        return $this->select()->where([ "t > $since_ts","channel"=>$channel])->orderBy("id")->commit();
    }

    /**
     * 获取指定频道的通知列表
     * @param int $channel 频道ID
     * @return array
     */
    public function getByChannel(int $channel): array
    {
        return $this->select()->where(["channel" => $channel])->orderBy("id")->commit();
    }

    /**
     * 统计指定频道的通知数量
     * @param int $channel 频道ID
     * @return int
     */
    public function countByChannel(int $channel): int
    {
        $result = $this->select("COUNT(*) as count")->where(["channel" => $channel])->commit();
        return (int)($result[0]['count'] ?? 0);
    }

    /**
     * 发布通知（DTO 风格）
     * @param int $channel 频道名称
     * @param string $title 标题
     * @param string $message 消息内容
     * @param string $type 消息类型：default, info, warning, error, success
     * @param string $actionLeftUrl 左侧按钮 URL
     * @param string $actionLeftText 左侧按钮文本
     * @param string $actionRightUrl 右侧按钮 URL
     * @param string $actionRightText 右侧按钮文本
     * @return NotificationModel
     * @throws DbFieldError
     */
    public function post(
        int $channel,
        string $title,
        string $message,
        string $type = "default",
        string $actionLeftUrl = "",
        string $actionLeftText = "",
        string $actionRightUrl = "",
        string $actionRightText = ""
    ): NotificationModel {
        $model = new NotificationModel();
        $model->channel = $channel;
        $model->title = urldecode($title);
        $model->message = urldecode($message);
        $model->type = urldecode($type);
        $model->actionLeftUrl = urldecode($actionLeftUrl);
        $model->actionLeftText = urldecode($actionLeftText);
        $model->actionRightUrl = urldecode($actionRightUrl);
        $model->actionRightText = urldecode($actionRightText);
        $model->is_read = false;
        $model->t = time();

        $model->id = $this->insertModel($model);

        // 随机清理过期通知
        if (rand(1, 100) == 1) {
            $this->delete()->where(["t < ".(time() - 3600 * 24 * 2)])->commit();
        }

        return $model;
    }
}
