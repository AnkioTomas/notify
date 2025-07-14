<?php

declare(strict_types=1);

namespace app\controller\notify;

use nova\framework\http\Response;
use nova\plugin\tpl\ViewResponse;

class Main extends BaseController
{
    protected ViewResponse $viewResponse;

    public function init(): ?Response
    {
        $ret = parent::init();
        if (!empty($ret)) {
            return $ret;
        }
        $this->viewResponse = new ViewResponse();

        $this->viewResponse->init(
            '',
            [
                'title' => 'AnkioのNotify',
                'header' => $this->userModel->avatar,
                'nickname' => $this->userModel->display_name,
            ]
        );

        if (!$this->request->isPjax()) {
            return $this->viewResponse->asTpl("layout");
        }

        return null;
    }

    public function index(): Response
    {
        return Response::asText();
    }
}
