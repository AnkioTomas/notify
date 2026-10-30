<?php

declare(strict_types=1);

namespace app\utils;

use nova\framework\core\Logger;
use Throwable;

class WechatException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        // 传 $this，让 Logger 按 Throwable 格式化；禁止塞 getTrace() 当 context（args 里一堆不可 print 的东西）
        Logger::error($this);
    }
}
