<?php

declare(strict_types=1);

namespace app\controller\notify;

use nova\framework\http\Response;
use nova\plugin\tpl\ViewResponse;

class Main extends BaseController
{
    protected ViewResponse $viewResponse;

    public function index(): ?Response
    {

        $this->viewResponse = new ViewResponse();

        $this->viewResponse->init(
            '',
            [
                'title' => 'AnkioのNotify',
                'header' => $this->userModel->avatar,
                'nickname' => $this->userModel->display_name,
            ]
        );

        return $this->viewResponse->asTpl("layout");
    }

}
