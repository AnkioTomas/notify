<?php

namespace app;

use nova\framework\App;
use nova\framework\route\Route;

class Application extends App
{
    protected function onFrameworkStart()
    {
        parent::onFrameworkStart();

        Route::getInstance()
            // 发布消息通知
            ->post("/{channel}_{token}",\nova\framework\route("index","main","publish"))
            ->get("/subscribe/{token}",\nova\framework\route("index","main","subscribe"))
            ->get("/read/{channel}/{id}/{token}",\nova\framework\route("index","main","read"))
            ;

    }
}