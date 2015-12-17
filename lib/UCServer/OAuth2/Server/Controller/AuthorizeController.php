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
            $authored = $this->ifAuthorized();
            return;
        }
        if (!$this->server->validateAuthorizeRequest($this->request, $this->response)) {
            $this->server->getResponse()->send();
            exit;
        }
        $this->view->assign('a', 1);
        $this->view->display('oauth2_login');
    }
    
    protected function ifAuthorized() {
        $username = $this->request->request('username');
        $password = $this->request->request('password');
        
        /*$ucmember_model = new \User\Api\UserApi();
        
        $uid = $ucmember_model->login($username, $password);//(bool) I('post.authorize');
        if ($uid < 1) {
            return false;
        }*/
        
        // call the oauth server and return the response
        $this->server->handleAuthorizeRequest($this->request, $this->response, true, 1);
        $this->server->getResponse()->send();
        
        return true;
    }
}