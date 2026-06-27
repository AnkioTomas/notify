<?php

declare(strict_types=1);

namespace app\utils;

use app\utils\crypto\Prpcrypt;
use app\utils\crypto\Sha1;
use nova\framework\core\Logger;
use nova\framework\json\Json;

/**
 * 企业微信回调消息加解密（对应官方 VerifyURL / DecryptMsg / EncryptMsg 三函数）。
 *
 * 协议要点（来自《加解密方案说明》）：
 * - AESKey = base64_decode(EncodingAESKey . '=')，32 字节
 * - AES-256-CBC，IV = AESKey 前 16 字节
 * - PKCS#7 填充按 32 字节为块
 * - 明文结构：random(16B) | msg_len(4B, network order) | msg | receive_id
 * - 签名：sha1(sort([token, timestamp, nonce, encrypt]))
 */
class WechatCrypt
{
    private string $m_sToken;
    private string $m_sEncodingAesKey;
    private string $m_sReceiveId;

    /**
     * 构造函数
     * @param $token          string 开发者设置的token
     * @param $encodingAesKey string 开发者设置的EncodingAESKey
     * @param $receiveId      string, 不同应用场景传不同的id
     */
    public function __construct(string $token, string $encodingAesKey, string $receiveId)
    {
        $this->m_sToken = $token;
        $this->m_sEncodingAesKey = $encodingAesKey;
        $this->m_sReceiveId = $receiveId;

        if (strlen($this->m_sEncodingAesKey) !== 43) {
            throw new \InvalidArgumentException("aes key length must be 43");
        }
    }

    /**
     * @param  string $timestamp
     * @param  string $nonce
     * @param  string $encrypt
     * @return string
     */
    private function sha1(string $timestamp, string $nonce, string $encrypt): string
    {
        $array = array($encrypt, $this->m_sToken, $timestamp, $nonce);
        sort($array, SORT_STRING);
        $str = implode($array);
        return sha1($str);
    }

    public function verifyURL($sMsgSignature, $sTimeStamp, $sNonce, $sEchoStr): string
    {

        $sha = $this->sha1($sTimeStamp, $sNonce, $sEchoStr);

        if ($sha !== $sMsgSignature) {
            return '';
        }

        $result = (new Prpcrypt($this->m_sEncodingAesKey))->decrypt($sEchoStr, $this->m_sReceiveId);

        return $result;
    }

    public function encryptMsg($sReplyMsg, $sTimeStamp, $sNonce): string
    {
        $encrypt = (new Prpcrypt($this->m_sEncodingAesKey))->encrypt($sReplyMsg, $this->m_sReceiveId);

        $sha = $this->sha1($sTimeStamp, $sNonce, $encrypt);

        return son::encode([
            'encrypt' => $encrypt,
            'msgsignature' => $sha,
            'timestamp' => $sTimeStamp,
            'nonce' => $sNonce,
        ]);
    }

    public function decryptMsg($sMsgSignature, $sTimeStamp, $sNonce, $sPostData): string
    {

        $encrypt = Json::decode($sPostData, true)['Encrypt'];

        $ts = $sTimeStamp;

        $sha = $this->sha1($ts, $sNonce, $encrypt);
        if ($sha !== $sMsgSignature) {
            Logger::warning('WechatCrypt decryptMsg: signature not match', [
                'computed_signature' => $sha,
                'msg_signature'      => $sMsgSignature,
            ]);
            return '';
        }

        $result = (new Prpcrypt($this->m_sEncodingAesKey))->decrypt($encrypt, $this->m_sReceiveId);

        return $result;
    }
}
