<?php

declare(strict_types=1);

namespace app\controller\index;

use app\database\dao\ChannelDao;
use app\database\dao\NotificationDao;
use app\database\model\ChannelModel;
use app\database\model\NotificationModel;
use app\utils\UserToken;
use nova\framework\http\Response;
use nova\framework\route\Controller;

class Main extends Controller
{
    public function publish($channel, $token): Response
    {
        /**
         * @var $channelModel ChannelModel
         */
        $channelModel = ChannelDao::getInstance()->find(null, ['channel' => $channel]);
        if (empty($channelModel) || $token != $channelModel->token) {
            return Response::asJson([
                "code" => 403,
                "msg" => "you are not authorized"
            ], 403);
        }

        // 请求参数

        $title = $this->request->arg('title', '');

        $content = $this->request->arg('content', '');

        $link = $this->request->arg('link', '');

        NotificationDao::getInstance($channelModel->channel)->post($title, $content, $link);

        return Response::asJson([
            "code" => 200,
        ]);
    }

    public function subscribe($token): Response
    {

        (new UserToken())->checkToken($token);

        $channels = ChannelDao::getInstance()->getAll();

        $before  = time() - 24 * 60 * 60;

        $since_ts = $this->request->get("since_ts", $before); //只拉取一天的通知

        if ($since_ts < $before) {
            $since_ts = $before;
        }

        $notifications = [];
        /**
         * @var $channel ChannelModel
         */
        foreach ($channels['data'] as $channel) {
            $data =  NotificationDao::getInstance($channel->channel)->getUnread($since_ts);
            if (empty($data)) {
                continue;
            }
            $notifications[$channel->channel] = $data;
            /**
             * @var $notification NotificationModel
             */
        }
        return Response::asJson([
            "code" => 200,
            "data" => $notifications
        ]);
    }

    public function read($channel, $id, $token): Response
    {
        (new UserToken())->checkToken($token);

        $channelModel = ChannelDao::getInstance()->find(null, ['channel' => $channel]);
        if (empty($channelModel)) {
            return Response::asJson([
                "code" => 403,
                "msg" => "you are not authorized"
            ], 403);
        }

        $notification = NotificationDao::getInstance($channelModel->channel)->find(null, ['id' => $id]);

        if (empty($notification)) {
            return Response::asJson([
                "code" => 403,
                "msg" => "you are not authorized"
            ], 403);
        }

        $notification->is_read = true;

        NotificationDao::getInstance($channelModel->channel)->updateModel($notification);

        return Response::asJson([
            "code" => 200,
        ]);
    }
}
