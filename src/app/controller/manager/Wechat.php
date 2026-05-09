<?php

declare(strict_types=1);

namespace app\controller\manager;

use function nova\framework\config;

use nova\framework\http\Response;

class Wechat extends BaseController
{
    private const array FIELDS = ['corpid', 'secret', 'to_user'];

    /**
     * 同一路径承载查询与保存：GET 取配置，POST 保存配置。
     */
    public function config(): Response
    {
        if ($this->request->isPost()) {
            foreach (self::FIELDS as $f) {
                config("work_wechat.$f", trim((string)$this->request->post($f, '')));
            }

            return Response::asJson([
                'code' => 200,
                'msg' => '保存成功',
            ]);
        }

        $data = [];
        foreach (self::FIELDS as $f) {
            $data[$f] = (string)(config("work_wechat.$f") ?? '');
        }

        return Response::asJson([
            'code' => 200,
            'data' => $data,
        ]);
    }
}
