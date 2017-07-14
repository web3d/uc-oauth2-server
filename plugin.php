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
    
    function control() {
		$this->pluginbase();
	}

    /**
     * 应用列表
     */
    public function onindex()
    {
        $this->init_schema();
        
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
    
    /**
     * 初始化db结构
     */
    private function init_schema()
    {
        if (!empty($this->plugin['install']) 
            && !$this->db->fetch_first("SHOW TABLES LIKE '%" . UC_DBTABLEPRE . "oauth_users%'")) {
            $this->run_sql($this->plugin['install']);
        }
    }
    
    /**
     * 从安装脚本中移植的方法
     * @param string $sql
     * @return void
     */
    private function run_sql($sql)
    {
        if (empty($sql))
            return;
        
        $origin_tablepre = 'uc_';

        $sql = str_replace("\r", "\n", str_replace(' ' . $origin_tablepre, ' ' . UC_DBTABLEPRE, $sql));
        $ret = array();
        $num = 0;
        foreach (explode(";\n", trim($sql)) as $query) {
            $ret[$num] = '';
            $queries = explode("\n", trim($query));
            foreach ($queries as $query) {
                $ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0] . $query[1] == '--') ? '' : $query;
            }
            $num++;
        }

        foreach ($ret as $query) {
            $query = trim($query);
            if (!$query) {
                continue;
            }
            
            if (substr($query, 0, 12) == 'CREATE TABLE') {
                $query = $this->fix_table_creation($query);
            }
            
            $this->db->query($query);
        }
    }
    
    /**
     * 从SQL中整理创建表的预防,主要是作版本兼容处理
     * @param string $sql
     * @return string
     */
    private function fix_table_creation($sql)
    {
        $type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $sql));
        $type = in_array($type, array('MYISAM', 'HEAP')) ? $type : 'MYISAM';
        return preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $sql) 
                . ($this->db->version() > '4.1' 
                    ? " ENGINE=$type DEFAULT CHARSET=" . UC_DBCHARSET 
                    : " TYPE=$type"
                );
    }

}
