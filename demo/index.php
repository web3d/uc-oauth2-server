<?php

/**
 * 客户端应用简易Demo
 * Client Demo由于和Server端放在一起,相关参数值的构造可能有点绕
 */

// 在客户端应用中根据实际情况定义服务端地址
$self_host = 'http://' . $_SERVER['HTTP_HOST'];
$server_host = $self_host;
$server_url = $server_host . dirname(dirname($_SERVER['SCRIPT_NAME']));

$client_id = 1;
// 应用对应的authkey
$client_secret = 'rfHb43Jfu4B0z85dK2Y9BbWbW8pfL7q7C3BcMf364ezeLdCepac1fdd47fqfo54f';
// 应用中填写的Redirect Uri
$redirect_uri = $self_host . dirname($_SERVER['SCRIPT_NAME']) . '/';

$code = $_GET['code'];
session_start();

if(!empty($code)) {
    // TODO 校验 state
    $state = $_GET['state'];
    
    var_dump($_REQUEST);
    
    require_once dirname(__FILE__) . '/HttpClient.class.php';
    
    // 换取access_token
    $contents = HttpClient::quickPost($server_url .'/token.php', array(
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri
    ));
    var_dump($contents);
} else {
    // 跳转 到服务端授权页面
    
    $_SESSION['state'] = rand(10000, 99999);
    $params = [
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'state' => $_SESSION['state']
    ];
    echo '<a href="' . $server_url . '/authorize.php?' . http_build_query($params) .'">authorize</a>';
}