<?php

namespace TimeCheer\UCServer\OAuth2\Server\Base;

use OAuth2\Server as OAuth2Server;

use OAuth2\Storage\Memory;
use OAuth2\OpenID\GrantType\AuthorizationCode;
use OAuth2\GrantType\UserCredentials;
use OAuth2\GrantType\RefreshToken;

/**
 * 控制器基类
 */
class Controller {
    
    protected $storage;
    
    /**
     *
     * @var \OAuth2\Server 
     */
    protected $server;
    
    /**
     *
     * @var \OAuth2\Request 
     */
    protected $request;
    
    /**
     *
     * @var \OAuth2\Response
     */
    protected $response;
    
    public function __construct() {
        $this->initServer();
        
        $this->request = \OAuth2\Request::createFromGlobals();
        $this->response = new \OAuth2\Response();
        
        $this->initialize();
    }

    /**
     * 供子类做初始化操作
     * @return boolean 
     */
    protected function initialize() {
        return true;
    }
    
    /**
     * 初始化oauth2 server对象
     */
    protected function initServer() {
        $storage = new Storage(array(
            'dsn' => 'mysql:dbname=' . UC_DBNAME . ';host=' . UC_DBHOST,
            'username' => UC_DBUSER,
            'password' => UC_DBPW,
            'tablePrefix' => UC_DBTABLEPRE
        ));
        
        // create array of supported grant types
        $grantTypes = array(
            'authorization_code' => new AuthorizationCode($storage),
            'user_credentials'   => new UserCredentials($storage),
            'refresh_token'      => new RefreshToken($storage, array(
                'always_issue_new_refresh_token' => true,
            )),
        );

        // instantiate the oauth server
        $this->server = new OAuth2Server($storage, array(
            'enforce_state' => true,
            'allow_implicit' => true,
            'access_lifetime' => 3600 * 24 * 30,
            'issuer' => $_SERVER['HTTP_HOST']), $grantTypes);
    }
    
}
