<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\controller\notify\BaseController;
use nova\framework\http\Response;
use nova\plugin\cookie\Session;
use nova\plugin\login\manager\PwdLoginManager;
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
            $menu =  [
                [
                    "title" => "通知渠道",
                    "icon" => "notifications",
                    "url" => "/notification",
                    "pjax" => true
                ],
                [
                    "title" => "账户安全",
                    "icon" => "person",
                    "url" => "/center",
                    "pjax" => true
                ],
            ];

            return $this->viewResponse->asTpl("layout", [
                'menuConfig' => $menu
            ]);
        }

        return null;
    }

    public function index(): Response
    {
        return Response::asRedirect("/notification");
    }

    public function notification(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function center(): Response
    {
        return $this->viewResponse->asTpl(PwdLoginManager::CENTER_TPL, [
            "username" => $this->userModel->username,
        ]);
    }

    public function logout(): Response
    {
        Session::getInstance()->destroy();
        return $this->redirectTo("/");
    }
}
