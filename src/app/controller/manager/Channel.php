<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\database\dao\AppDao;
use app\database\model\AppModel;
use nova\framework\http\Response;

class Channel extends BaseController
{
    public function list(): Response
    {
        $where = [];

        $page = $this->request->get("page", 1);
        $size = $this->request->get("pageSize", 10);

        $data = AppDao::getInstance()->getAll([], $where, $page, $size, "id");
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
        AppDao::getInstance()->remove($id);
        return Response::asJson([
            'code' => 200,
        ]);

    }

    public function edit(): Response
    {
        $app = new AppModel($_POST);
        AppDao::getInstance()->insertModel($app, true);
        return Response::asJson([
            'code' => 200,
            'msg' => '操作成功'
        ]);
    }
}
