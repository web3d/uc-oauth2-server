<?php

!defined('IN_UC') && exit('Access Denied');

/**
 * oauth2插件后台入口文件
 */
class control extends pluginbase {

    public function control() {
        $this->pluginbase();

        if (!$this->user['isfounder'] && !$this->user['allowadminapp']) {
            $this->message('no_permission_for_this_module');
        }
        $this->load('app');
        $this->load('misc');
    }

    /**
     * 应用列表
     */
    public function onindex() {
        $status = $affectedrows = 0;
        if ($this->submitcheck() && !empty($_POST['delete'])) {
            $affectedrows += $_ENV['app']->delete_apps($_POST['delete']);
            foreach ($_POST['delete'] as $k => $appid) {
                $_ENV['app']->alter_app_table($appid, 'REMOVE');
                unset($_POST['name'][$k]);
            }
            $this->load('cache');
            $_ENV['cache']->updatedata();
            $this->writelog('app_delete', 'appid=' . implode(',', $_POST['delete']));
            $status = 2;

            $this->_add_note_for_app();
        }

        $a = getgpc('a');
        $applist = $_ENV['app']->get_apps();
        $this->view->assign('status', $status);
        $this->view->assign('a', 'ls');
        $this->view->assign('applist', $applist);

        $this->view->display('plugin_oauth2_admin_app');
    }

}
