<?php
/**
 * TOKEN服务
 */

require_once(dirname(__FILE__) . '/bootstrap.inc.php');

$server->addGrantType(new OAuth2_GrantType_ClientCredentials($storage));

// Handle a request for an OAuth2.0 Access Token and send the response to the client
$server->handleTokenRequest(OAuth2_Request::createFromGlobals(), new OAuth2_Response())->send();