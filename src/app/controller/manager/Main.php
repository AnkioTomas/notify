<?php

declare(strict_types=1);

namespace app\controller\manager;

use app\Application;
use app\database\dao\AppDao;
use app\database\model\AppModel;

use nova\framework\http\Response;
use nova\plugin\login\LoginTpl;
use nova\plugin\login\manager\PwdLoginManager;
use nova\plugin\login\manager\SSOLoginManager;
use nova\plugin\login\route\PermissionRouter;
use nova\plugin\tpl\ViewResponse;

class Main extends BaseController
{
    protected ViewResponse $viewResponse;

    public function init(): ?Response
    {
        $data = parent::init();
        if (!empty($data)) {
            return $data;
        }
        $this->viewResponse = new ViewResponse();

        $this->viewResponse->init(
            '',
            [
                'title' => Application::SYSTEM_NAME,
                'header' => $this->userModel->avatar,
            ]
        );

        if (!$this->request->isPjax()) {

            $menu =  [
                [
                    "title" => "通知列表",
                    "icon" => "campaign",
                    "pjax" => true,
                    "sub" => $this->subMenus()
                ],
                [
                    "title" => "通知渠道",
                    "icon" => "notifications",
                    "url" => "/channel",
                    "pjax" => true
                ],
                [
                    "title" => "发布通知",
                    "icon" => "rss_feed",
                    "url" => "/token",
                    "pjax" => true
                ],
                [
                    "title" => "企业微信",
                    "icon" => "forum",
                    "url" => "/wechat",
                    "pjax" => true
                ],

                LoginTpl::getInstance()->menu()
            ];

            return $this->viewResponse->asTpl("layout", [
                'menuConfig' => PermissionRouter::getInstance()->filterMenu($menu, $this->userModel->role()->permissions)
            ]);
        }

        return LoginTpl::getInstance()->route($this->viewResponse, $this->request);
    }

    public function index(): Response
    {
        return Response::asRedirect("/channel");
    }

    public function notifications(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function channel(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function account(): Response
    {
        return $this->viewResponse->asTpl(PwdLoginManager::TPL_PASSWORD, [
            "username" => $this->userModel->username,
        ]);
    }

    public function sso(): Response
    {
        return $this->viewResponse->asTpl(SSOLoginManager::TPL_SSO);
    }

    public function token(): Response
    {
        return $this->viewResponse->asTpl();
    }

    public function wechat(): Response
    {
        return $this->viewResponse->asTpl();
    }

    private function subMenus(): array
    {
        $menu = [
            [
                "title" => "全部通知",
                "icon" => "",
                "url" => "/notifications",
                "pjax" => true,
                "match" => "^/notifications(?!\?.*app_id=)($|\?)",
            ],
        ];

        /**
         * @var $item AppModel
         */
        foreach (AppDao::getInstance()->list() as $item) {
            $menu[] = [
                "title" => $item->name,
                "icon" => "",
                "url" => "/notifications?app_id=".$item->id,
                "pjax" => true,
                "match" => "^/notifications\?([^#]*&)?app_id=" . $item->id . "(&|$)",
            ];
        }

        return $menu;
    }

}
