<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\controller\notify\BaseController;
use app\database\dao\ChannelDao;
use app\database\dao\NotificationDao;
use app\database\model\ChannelModel;
use nova\framework\http\Response;

class Channel extends BaseController
{
    public function list(): Response
    {
        $where = [];

        $page = $this->request->get("page", 1);
        $size = $this->request->get("pageSize", 10);

        $data = ChannelDao::getInstance()->getAll([], $where, $page, $size, true, "id");
        $total = $data['total'];
        $data = $data['data'] ?: [];
        return Response::asJson([
            'code' => 200,
            'count' => $total,
            'data' => $data,
        ]);
    }

    public function del(): Response
    {
        $id =  $this->request->post("id", 0);
        /**
         * @var $channel ChannelModel
         */
        $channel = ChannelDao::getInstance()->find(null, ['id' => $id ]);
        if (empty($channel)) {
            return Response::asJson([
                'code' => 404,
                'msg' => '渠道不存在',
            ]);
        }
        ChannelDao::getInstance()->delete()->where(['id' => $this->request->post("id", 0)])->commit();
        // 删除该频道的所有通知
        NotificationDao::getInstance()->delete()->where(['channel' => $channel->id])->commit();
        return Response::asJson([
            'code' => 200,
        ]);

    }

    public function edit(): Response
    {
        $app = new ChannelModel($_POST);
        if ($app->id > 0) {
            /**
             * @var $item ChannelModel
             */
            $item = ChannelDao::getInstance()->find(null, ['id' => $app->id]);
            if (!empty($item)) {
                $app->channel = $item->channel;
            }
            ChannelDao::getInstance()->updateModel($app);
        } else {
            $app->token = substr(md5(uniqid()), 8, 8);
            ChannelDao::getInstance()->insertModel($app);
        }

        return Response::asJson([
            'code' => 200,
            'msg' => '操作成功'
        ]);
    }
}
