<?php

namespace app\controller\index;

use app\database\dao\ChannelDao;
use app\database\dao\NotificationDao;
use app\database\model\ChannelModel;
use nova\framework\cache\Cache;
use nova\framework\http\Response;
use nova\framework\route\Controller;

class Main extends Controller
{
    function publish($channel,$token): Response
    {
        /**
         * @var $channelModel ChannelModel
         */
        $channelModel = ChannelDao::getInstance()->find(null,['channel'=>$channel]);
        if(empty($channelModel) || $token!=$channelModel->token){
            return Response::asJson([
                "code"=>403,
                "msg" => "you are not authorized"
            ],403);
        }

        // 请求参数

        $title = $this->request->post('title');

        $content = $this->request->post('content');

        NotificationDao::getInstance($channelModel->channel)->post($title,$content);

        return Response::asJson([
            "code" =>200,
        ]);
    }

    function subscribe($token): Response
    {

        $cache = new Cache();
        if($cache->get("token")!=$token){
            return Response::asJson([
                "code"=>403,
                "msg" => "you are not authorized"
            ],403);
        };

        $channels = ChannelDao::getInstance()->getAll();
        $notifications = [];
        /**
         * @var $channel ChannelModel
         */
        foreach ($channels as $channel) {
            $data =  NotificationDao::getInstance($channel->channel)->getUnread();
            if(empty($data)){
                continue;
            }
            $notifications[$channel->channel] = $data;
        }
        return Response::asJson([
            "code"=>200,
            "data" =>$notifications
        ]);
    }

    function read($channel,$id,$token): Response
    {
        $cache = new Cache();
        if($cache->get("token")!=$token){
            return Response::asJson([
                "code"=>403,
                "msg" => "you are not authorized"
            ],403);
        };
        $channelModel = ChannelDao::getInstance()->find(null,['channel'=>$channel]);
        if (empty($channelModel)) {
            return Response::asJson([
                "code"=>403,
                "msg" => "you are not authorized"
            ],403);
        }

        $notification = NotificationDao::getInstance($channelModel->channel)->find(null,['id'=>$id]);

        if (empty($notification)) {
            return Response::asJson([
                "code"=>403,
                "msg" => "you are not authorized"
            ],403);
        }

        $notification->is_read = true;

        NotificationDao::getInstance($channelModel->channel)->updateModel($notification);

        return Response::asJson([
            "code"=>200,
        ]);
    }
}