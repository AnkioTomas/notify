<?php

declare(strict_types=1);

namespace app\controller\notify;

use app\Application;
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
                'title' => Application::SYSTEM_NAME,
                'header' => $this->userModel->avatar,
            ]
        );
        return $this->viewResponse->asTpl("layout");
    }

}
