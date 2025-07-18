<?php

declare(strict_types=1);

namespace app\utils;

use nova\framework\cache\Cache;
use nova\framework\core\Context;
use nova\framework\exception\AppExitException;
use nova\framework\http\Response;

class UserToken
{
    private Cache $cache;
    public function __construct()
    {
        $this->cache = Context::instance()->cache;
    }

    /**
     * @throws AppExitException
     */
    public function checkToken($token)
    {
        if (empty($token)) {
            throw new AppExitException(Response::asJson([
                "code" => 403,
                "message" => "Invalid Token"
            ]));
        }

        $this->cache = Context::instance()->cache;
        $t = $this->cache->get("token");
        if (empty($t)) {
            $t = $this->createToken();
            $this->cache->set("token", $t);
        }

        if ($token != $t) {
            throw new AppExitException(Response::asJson([
                "code" => 403,
                "message" => "Invalid Token"
            ]));
        }
    }

    public function resetToken()
    {
        $this->cache->set("token", $this->createToken());
    }

    public function createToken(): string
    {
        return substr(uniqid(), 0, 6);
    }

    public function getToken(): string
    {
        $token = $this->cache->get("token");
        if (empty($token)) {
            $token = $this->createToken();
            $this->cache->set("token", $token);
        }
        return $token;
    }
}
