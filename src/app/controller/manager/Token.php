<?php

declare(strict_types=1);

namespace app\controller\manager;

use function nova\framework\config;

use nova\framework\http\Response;

use function nova\framework\uuid;

use nova\plugin\login\controller\BaseAPIController;

class Token extends BaseAPIController
{
    protected ?string $token = null;
    public function init(): ?Response
    {
        $this->token = config('authorization');
        return parent::init();
    }

    public function reset(): Response
    {
        $this->token = md5(uuid());
        config('authorization', $this->token);
        return Response::asJson([
            'code' => 200,
            'msg' => '操作成功',
        ]);
    }

    public function get(): Response
    {
        return Response::asJson([
            'code' => 200,
            'data' => $this->token,
        ]);
    }
}
