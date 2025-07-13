<?php

declare(strict_types=1);

namespace app\controller\notify;

use nova\framework\http\Response;
use nova\framework\route\Controller;
use nova\plugin\cookie\Session;
use nova\plugin\login\db\Model\UserModel;
use nova\plugin\login\LoginManager;

class BaseController extends Controller
{
    protected ?UserModel $userModel;
    public function init(): ?Response
    {
        Session::getInstance()->start();
        $this->userModel = LoginManager::getInstance()->checkLogin();
        if (empty($this->userModel)) {
            return $this->redirectTo(LoginManager::getInstance()->redirectLogin());
        }
        return null;

    }

    protected function redirectTo($link): Response
    {
        if (!$this->request->isPjax()) {
            //跳转后台
            return Response::asRedirect($link);
        } else {
            return Response::asHtml(
                <<<EOF
<title id="title">302 Redirect</title>
<style id="style"></style>
<div id="container" class="container"></div>
<script id="script">
    window.pageLoadFiles = [];
    window.pageOnLoad = function (loading) {
        location.replace('$link');
        return false
    };
</script>
EOF
            );
        }
    }
}
