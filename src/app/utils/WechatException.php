<?php

declare(strict_types=1);

namespace app\utils;

use nova\framework\core\Logger;
use Throwable;

class WechatException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        Logger::error($message, $this->getTrace());
    }
}
