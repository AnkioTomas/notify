<?php
return array (
  'debug' => true,
  'timezone' => 'Asia/Shanghai',
  'default_route' => true,
  'domain' => 
  array (
    0 => '0.0.0.0',
  ),
  'installed' => false,
  'version' => '1.0.2',
  'db' => 
  array (
    'host' => '127.0.0.1',
    'type' => 'mysql',
    'port' => 3306,
    'username' => 'your_db_user',
    'password' => 'your_db_password',
    'db' => 'your_db_name',
    'charset' => 'utf8mb4',
  ),
  'framework_start' => 
  array (
    0 => 'nova\\plugin\\installer\\InstallerManager',
    1 => 'nova\\plugin\\login\\LoginManager',
    2 => 'nova\\plugin\\tpl\\Handler',
  ),
  'login' => 
  array (
    'allowedLoginCount' => 1,
    'loginCallback' => '/',
    'systemName' => 'Notify Center',
    'ssoEnable' => false,
    'ssoProviderUrl' => 'https://sso.example.com',
    'ssoClientId' => 'your_sso_client_id',
    'ssoClientSecret' => 'your_sso_client_secret',
    'ssoMustHasAccount' => true,
  ),
  'session' => 
  array (
    'time' => 2592000,
    'session_name' => 'NovaSession',
  ),
  'work_wechat' => 
  array (
    'default' => 'notify',
    'corpid' => 'your_corp_id',
    'to_user' => 'your_user_id',
    'token' => 'your_wechat_callback_token',
    'aes_key' => 'your_wechat_callback_aes_key',
  ),
  'authorization' => 'your_authorization_token',
);
