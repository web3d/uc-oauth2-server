<?php

require_once UC_ROOT . 'lib/template.class.php';

class OAuth2_Template extends template {
    
    /**
     * 增加.前缀特性，以便支持改变模板目录到扩展模块目录下
     * @param string $file
     * @return string
     */
    public function gettpl($file) {
        if (stripos($file, '.') === 0) {
            $this->tpldir = UC_OAUTHDIR.'./view/default';
            $this->defaulttpldir = $this->tpldir;

            $objdir = UC_DATADIR.'./view/oauth2';
            if (!is_dir($objdir)) {
                mkdir($objdir, 777);
            }
            $this->objdir = $objdir;
        } else {
            $this->defaulttpldir = UC_ROOT.'./view/default';
            $this->tpldir = $this->defaulttpldir;
            $this->objdir = UC_DATADIR.'./view';
        }
        
        return parent::gettpl(trim($file, '.'));
	}
}