<?php
declare(strict_types=1);
require_once __DIR__.'/../config_facebook.php';

const FB_OAUTH='https://www.facebook.com/v16.0/dialog/oauth';
const FB_TOKEN='https://graph.facebook.com/v16.0/oauth/access_token';
const FB_ME   ='https://graph.facebook.com/me';

function fb_auth_url(): string {
  if (!defined('FB_CLIENT_ID') || !defined('FB_REDIRECT_URI')) throw new RuntimeException('Missing FB_CLIENT_ID/FB_REDIRECT_URI');
  $_SESSION['fb_state']=bin2hex(random_bytes(16));
  $q=['client_id'=>FB_CLIENT_ID,'redirect_uri'=>FB_REDIRECT_URI,'state'=>$_SESSION['fb_state'],
      'scope'=>'email,public_profile','response_type'=>'code'];
  return FB_OAUTH.'?'.http_build_query($q);
}
function fb_exchange_token(string $code): ?array {
  if (!defined('FB_CLIENT_ID') || !defined('FB_CLIENT_SECRET') || !defined('FB_REDIRECT_URI')) return null;
  $url=FB_TOKEN.'?'.http_build_query(['client_id'=>FB_CLIENT_ID,'redirect_uri'=>FB_REDIRECT_URI,'client_secret'=>FB_CLIENT_SECRET,'code'=>$code]);
  $raw=@file_get_contents($url); return $raw?json_decode($raw,true):null;
}
function fb_userinfo(string $accessToken): ?array {
  $url=FB_ME.'?'.http_build_query(['fields'=>'id,name,email','access_token'=>$accessToken]);
  $raw=@file_get_contents($url); return $raw?json_decode($raw,true):null;
}
