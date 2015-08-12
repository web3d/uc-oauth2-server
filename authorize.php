<?php

require_once(dirname(__FILE__) . '/lib/Bootstrap.php');

Bootstrap::init();
$server = Bootstrap::runOAuth2Server();
$storage = Bootstrap::getStorage();

$request = OAuth2_Request::createFromGlobals();
$response = new OAuth2_Response();

// validate the authorize request
if (!$server->validateAuthorizeRequest($request, $response)) {
    $response->send();
    die;
}

$msg = '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
if (!empty($_POST)) {
    // print the authorization code if the user has authorized your client
    $is_authorized = $storage->checkUserCredentials($username, $password);
    //通常还有一步 同意客户端申请的访问权限

    if ($is_authorized) {
        $server->handleAuthorizeRequest($request, $response, $is_authorized);
        $response->send();

        exit;
    }
    
    $msg = '用户信息验证失败，请重新尝试';
}

// display an authorization form
//TODO:根据client_id 获取该应用的名称及相应信息、可以判断是否存在或是否被禁用
require_once UC_OAUTHDIR . 'lib/OAuth2/Template.php';
$viewer = new OAuth2_Template();

$viewer->assign('charset', UC_CHARSET);
$viewer->assign('msg', $msg);
$viewer->assign('username', $username);
$viewer->assign('password', $password);

$viewer->display('.oauth2_login');
