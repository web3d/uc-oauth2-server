<?php
/**
 * TOKEN服务
 */

require_once(dirname(__FILE__) . '/lib/Bootstrap.php');

Bootstrap::init();
$server = Bootstrap::runOAuth2Server(Bootstrap::GRANTTYPE_CLIENT);

// Handle a request for an OAuth2.0 Access Token and send the response to the client
$server->handleTokenRequest(OAuth2_Request::createFromGlobals(), new OAuth2_Response())->send();