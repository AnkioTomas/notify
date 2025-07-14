<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\controller\notify\BaseController;
use app\utils\UserToken;
use nova\framework\http\Response;

class Sub extends BaseController
{
    protected ?UserToken $token = null;
    public function init(): ?Response
    {
        $this->token = new UserToken();
        return parent::init();
    }

    public function reset(): Response
    {
        $this->token->resetToken();
        return Response::asJson([
            'code' => 200,
            'msg' => '操作成功',
        ]);
    }

    public function get(): Response
    {
        return Response::asJson([
            'code' => 200,
            'data' => $this->token->getToken(),
        ]);
    }
}
