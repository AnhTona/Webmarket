<?php
declare(strict_types=1);
require_once __DIR__.'/../config_google.php';

const G_OAUTH='https://accounts.google.com/o/oauth2/v2/auth';
const G_TOKEN='https://oauth2.googleapis.com/token';
const G_USER ='https://www.googleapis.com/oauth2/v3/userinfo';

function g_auth_url(): string {
  if (!defined('CLIENT_ID') || !defined('REDIRECT_URI')) throw new RuntimeException('Missing Google CLIENT_ID/REDIRECT_URI');
  $_SESSION['g_state']=bin2hex(random_bytes(16));
  $q=['client_id'=>CLIENT_ID,'redirect_uri'=>REDIRECT_URI,'response_type'=>'code',
      'scope'=>'openid email profile','state'=>$_SESSION['g_state'],'access_type'=>'online'];
  return G_OAUTH.'?'.http_build_query($q);
}
function g_exchange_token(string $code): ?array {
  if (!defined('CLIENT_ID') || !defined('CLIENT_SECRET') || !defined('REDIRECT_URI')) return null;
  $ch=curl_init(G_TOKEN);
  curl_setopt_array($ch,[CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>[
    'code'=>$code,'client_id'=>CLIENT_ID,'client_secret'=>CLIENT_SECRET,
    'redirect_uri'=>REDIRECT_URI,'grant_type'=>'authorization_code'
  ], CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15]);
  $raw=curl_exec($ch); $ok=curl_getinfo($ch,CURLINFO_HTTP_CODE)===200; curl_close($ch);
  return $ok?json_decode($raw,true):null;
}
function g_userinfo(string $accessToken): ?array {
  $ch=curl_init(G_USER.'?'.http_build_query(['access_token'=>$accessToken]));
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15]);
  $raw=curl_exec($ch); $ok=curl_getinfo($ch,CURLINFO_HTTP_CODE)===200; curl_close($ch);
  return $ok?json_decode($raw,true):null;
}
