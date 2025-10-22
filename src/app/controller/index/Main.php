<?php

declare(strict_types=1);

namespace app\controller\index;

use app\database\dao\ChannelDao;
use app\database\dao\NotificationDao;
use app\utils\UserToken;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;
use nova\framework\route\Controller;
use nova\plugin\orm\exception\DbFieldError;

class Main extends Controller
{
    /**
     * 发布通知（支持直接header映射和ntfy兼容）
     * POST /publish/{channel}/{token}
     */
    public function publish(string $channel, string $token): Response
    {
        $channelModel = ChannelDao::getInstance()->find(null, ['channel' => $channel]);
        if (empty($channelModel) || $token !== $channelModel->token) {
            return Response::asJson(["msg" => "Unauthorized", "code" => 403,], 403);
        }

        $model = NotificationDao::getInstance()->post(
            $channelModel->id,
            $this->request->getHeaderValue('Title') ?? '',
            $this->request->getHeaderValue('Type')?? 'default',
            $this->request->getHeaderValue('Action-Left-Url')?? '',
            $this->request->getHeaderValue('Action-Left-Text')?? '',
            $this->request->getHeaderValue('Action-Right-Url')?? '',
            $this->request->getHeaderValue('Action-Right-Text')?? '',
        );

        return Response::asJson(["msg" => "Success", "code" => 200, "data" => $model], 200);
    }

    /**
     * 获取所有通知列表
     * GET /list/{token}
     * @throws AppExitException|DbFieldError
     */
    public function list(string $token): Response
    {
        (new UserToken())->checkToken($token);

        $notifications = [];

        // 获取所有频道
        $channels = ChannelDao::getInstance()->getAll()['data'];
        
        // 收集所有频道的通知
        foreach ($channels as $channel) {
            $channelNotifications = NotificationDao::getInstance()->getByChannel($channel->id);
            if (!empty($channelNotifications)) {
                foreach ($channelNotifications as $notification) {
                    $notification->channel_name = $channel->channel;
                    $notifications[] = $notification;
                }
            }
        }



        return Response::asJson([
            "code" => 200,
            "data" => $notifications
        ]);
    }

    /**
     * SSE 实时订阅（推荐）
     * GET /sse/{token}
     */
    public function sse(string $token): Response
    {
        (new UserToken())->checkToken($token);

        return Response::asSSE(function ($emit) use ($token) {
            $lastCheck = time() - 5;


            while (!connection_aborted()) {
                $channels = ChannelDao::getInstance()->getAll()['data'];
                // 检查所有频道的新通知
                foreach ($channels as $channel) {
                    $notifications = [];
                    $data = NotificationDao::getInstance()->lastest($channel->id, $lastCheck);
                    if (!empty($data)) {
                        $lastCheck = time();
                        $notifications[$channel->channel] = $data;
                        $emit(json_encode($notifications), 'message');

                    }
                }

                // 每 3 秒发送心跳
                if (time() % 3 === 0) {
                    $emit(json_encode(["heartbeat" => time()]), 'heartbeat');
                }

                sleep(1);
            }
        });
    }

    /**
     * 标记通知为已读
     * GET /read/{id}/{token}
     */
    public function read(int $id, string $token): Response
    {
        (new UserToken())->checkToken($token);

        $notification = NotificationDao::getInstance()->find(null, ['id' => $id]);
        if (empty($notification)) {
            return Response::asJson(["msg" => "Notification not found", "code" => 404,], 404);
        }

        $notification->is_read = true;
        NotificationDao::getInstance()->updateModel($notification);

        return Response::asJson(["code" => 200]);
    }
}
