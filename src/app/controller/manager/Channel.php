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

        $page = (int)$this->request->get("page", 1);
        $size = (int)$this->request->get("pageSize", 10);

        $result = AppDao::getInstance()->getAll([], $where, $page, $size, "id");
        $total = $result['total'];
        $rows = $result['data'] ?: [];

        $data = [];
        foreach ($rows as $row) {
            if ($row instanceof AppModel) {
                $data[] = [
                    'id'         => $row->id,
                    'name'       => $row->name,
                    'short_name' => $row->short_name,
                    'agent_id'   => $row->agent_id,
                ];
                continue;
            }
            if (is_array($row)) {
                $data[] = [
                    'id'         => (int)($row['id'] ?? 0),
                    'name'       => (string)($row['name'] ?? ''),
                    'short_name' => (string)($row['short_name'] ?? ''),
                    'agent_id'   => (string)($row['agent_id'] ?? ''),
                ];
            }
        }

        return Response::asJson([
            'code'  => 200,
            'count' => $total,
            'data'  => $data,
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
