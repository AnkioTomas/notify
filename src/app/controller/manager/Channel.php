<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\database\dao\AppDao;
use app\database\model\AppModel;
use nova\framework\http\Response;
use nova\plugin\login\controller\BaseAPIController;

class Channel extends BaseAPIController
{
    public function list(): Response
    {
        $where = [];

        $page = (int)$this->request->get("page", 1);
        $size = (int)$this->request->get("pageSize", 10);

        $result = AppDao::getInstance()->getAll([], $where, $page, $size, "id");
        $total = $result['total'];
        $rows = $result['data'] ?: [];

        return Response::asJson([
            'code'  => 200,
            'count' => $total,
            'data'  => $rows,
        ]);
    }

    public function del(): Response
    {
        $id =  $this->request->post("id", 0);
        AppDao::getInstance()->remove($id);
        return Response::asJson([
            'code' => 200,
        ]);

    }

    public function edit(): Response
    {
        $app = new AppModel($_POST);
        $dao = AppDao::getInstance();

        $owner = $dao->shortName($app->short_name);
        if ($owner !== null && $owner->id !== $app->id) {
            return Response::asJson(['code' => 400, 'msg' => "短标识 {$app->short_name} 已被渠道「{$owner->name}」使用"]);
        }

        if ($app->id > 0) {
            $dao->updateModel($app);
        } else {
            $dao->insertModel($app);
        }
        return Response::asJson([
            'code' => 200,
            'msg' => '操作成功'
        ]);
    }
}
