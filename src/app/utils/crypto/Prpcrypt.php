<?php

declare(strict_types=1);

namespace app\utils\crypto;

class Prpcrypt
{
    private $key;
    private $iv;

    public function __construct($k)
    {
        // 兼容微信的 Key 处理逻辑
        $this->key = base64_decode($k . '=');
        $this->iv  = substr($this->key, 0, 16);
    }

    public function encrypt($text, $receiveId)
    {
        // 1. 严格按照微信协议拼接报文
        $randomStr = openssl_random_pseudo_bytes(16); // 代替原有的 getRandomStr
        $msg = $randomStr . pack('N', strlen($text)) . $text . $receiveId;

        // 2. 使用 OPENSSL_RAW_DATA 默认就是 PKCS7 填充，无需手动填充类
        $encrypted = openssl_encrypt($msg, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv);

        return base64_encode($encrypted);
    }

    public function decrypt($encrypted, $receiveId)
    {
        // 1. 解密（自动处理 PKCS7 反填充）
        $decrypted = openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv);
        if (!$decrypted) {
            return null;
        }

        // 2. 拆解微信协议报文
        $content = substr($decrypted, 16); // 跳过16位随机串
        $lenList = unpack('N', substr($content, 0, 4));
        $xmlLen  = $lenList[1];
        $xmlContent = substr($content, 4, $xmlLen);
        $fromReceiveId = substr($content, $xmlLen + 4);

        // 3. 校验 ReceiveId
        if ($fromReceiveId !== $receiveId) {
            return null; // ErrorCode::$ValidateCorpidError
        }

        return $xmlContent;
    }
}
