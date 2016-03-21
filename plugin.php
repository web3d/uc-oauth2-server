<?php

!defined('IN_UC') && exit('Access Denied');

/**
 * oauth2插件后台入口文件
 */
class control extends pluginbase
{

    public function __construct()
    {
        parent::__construct();
        if (!$this->user['isfounder'] && !$this->user['allowadminapp']) {
            $this->message('no_permission_for_this_module');
        }
        $this->load('app');
        $this->load('misc');
    }

    /**
     * 应用列表
     */
    public function onindex()
    {
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

        $this->view->display('plugin_oauth2_admin_app_ls');
    }

    public function onedit()
    {
        $appid = getgpc('appid');
        $updated = false;
        $app = $_ENV['app']->get_app_by_appid($appid);
        if ($this->submitcheck()) {

            $redirect_uri = getgpc('redirect_uri', 'P');
            $is_mobile = getgpc('is_mobile', 'P') ? 1 : 0;
            if ($is_mobile) {
                $redirect_uri = 'oob';
            }

            $this->db->query("UPDATE " . UC_DBTABLEPRE . "applications SET redirect_uri='{$redirect_uri}', is_mobile='{$is_mobile}' WHERE appid='$appid'");
            $updated = true;
            $this->load('cache');
            $_ENV['cache']->updatedata('apps');
            $this->cache('settings');
            $this->writelog('app_edit', "appid=$appid");

            $this->add_note_for_app();
            $app = $_ENV['app']->get_app_by_appid($appid);
        }

        $this->view->assign('a', getgpc('a'));

        $this->view->assign('isfounder', $this->user['isfounder']);
        $this->view->assign('app', $app);

        $this->view->display('plugin_oauth2_admin_app_edit');
    }

    protected function add_note_for_app()
    {
        $this->load('note');
        
        $notedata = $this->db->fetch_all("SELECT appid, type, name, url, ip, viewprourl, apifilename, charset, synlogin, extra, recvnote FROM " . UC_DBTABLEPRE . "applications");
        $notedata = $this->format_notedata($notedata);
        $notedata['UC_API'] = UC_API;
        
        $_ENV['note']->add('updateapps', '', $this->serialize($notedata, 1));
        $_ENV['note']->send();
    }

    protected function format_notedata($notedata)
    {
        $arr = array();
        foreach ($notedata as $key => $note) {
            $note['extra'] = unserialize($note['extra']);
            $arr[$note['appid']] = $note;
        }
        return $arr;
    }

}
