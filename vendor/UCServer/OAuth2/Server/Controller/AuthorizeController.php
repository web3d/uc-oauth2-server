<?php

namespace TimeCheer\UCServer\OAuth2\Server\Controller;

use TimeCheer\UCServer\OAuth2\Server\Base\Controller;
use TimeCheer\UCServer\OAuth2\Server\Base\Template;

/**
 * 授权控制器
 */
class AuthorizeController extends Controller {
    
    protected $view;
    
    protected function initialize() {
        parent::initialize();
        
        $this->view = new Template();
    }

    /**
     * 用户跳转到该页面,先登陆;登录成功后,出现确认授权及选择授权范围的;用户确认后,再跳回
     * 要请求的scope参数在客户端应用跳转过来时构造
     * 不过授权范围确认页面现在好像普遍隐藏了 应该是同意过就记住
     */
    public function index() {
        if (!empty($_POST)) {
            // 标准流程
            $username = $this->ifAuthorized();
            // call the oauth server and return the response
            $this->server->handleAuthorizeRequest($this->request, $this->response, (bool) $username, $username);
            $this->server->getResponse()->send();
            return;
        }
        if (!$this->server->validateAuthorizeRequest($this->request, $this->response)) {
            $this->server->getResponse()->send();
            exit;
        }
        
        // 默认 authorization_code 先显示用户登录授权界面
        $this->view->assign('a', 1);
        $this->view->display('oauth2_login');
    }
    
    /**
     * 标准授权流程的前置校验流程,验证功能共用password类型的功能
     * @return boolean|string username
     */
    protected function ifAuthorized() {
        $response_type = $this->request->query('response_type');
        
        // 需要用户在授权界面上登录
        if ('code' == $response_type) {
            $username = trim($this->request->request('username'));
            $password = $this->request->request('password');
            
            $storage = $this->server->getStorage('user_credentials');
            
            $user = $storage->getUser($username);
            if (!$user) {
                return false;
            }
            $result = $storage->checkPassword($user, $password);
            
            return $result ? $user['user_id'] : false;
        }
        
        return false;
    }
}