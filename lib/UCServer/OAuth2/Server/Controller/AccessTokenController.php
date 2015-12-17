<?php

namespace TimeCheer\UCServer\OAuth2\Server\Controller;

use TimeCheer\UCServer\OAuth2\Server\Base\Controller;

/**
 * 换取Access Token
 */
class AccessTokenController extends Controller {
    
    public function index() {
        $this->server->handleTokenRequest($this->request, $this->response);
        $this->server->getResponse()->send();
    }
}