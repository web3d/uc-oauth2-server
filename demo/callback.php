<?php

$code = $_GET['code'];
$state = $_GET['state'];

if(!empty($code)){
    require_once dirname(__FILE__) . '/HttpClient.class.php';
    
    $contents = HttpClient::quickPost('http://127.0.0.1/chaomabang/uc_server/oauth2/access_token.php', array(
        'client_id' => 'testclient',
        'client_secret' => 'xxxxxx',
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => 'http://127.0.0.1//chaomabang/uc_server/oauth2_demo/callback.php'
    ));
    var_dump($contents);
}