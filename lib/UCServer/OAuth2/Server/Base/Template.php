<?php

namespace TimeCheer\UCServer\OAuth2\Server\Base;

/**
 * 将原来的模板引擎复制过来,重构
 */
class Template {

    protected $tplDir;
    protected $defaultTplDir;
    protected $objDir;
    protected $tplFile;
    protected $objFile;
    protected $langFile;
    protected $vars;
    protected $force = 0;
    protected $regexpVar = "\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*";
    protected $regexpVTag = "\<\?=(\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)\?\>";
    protected $regexpConst = "\{([\w]+)\}";
    protected $languages = array();
    protected $sid;

    public function __construct() {
        ob_start();
        $this->defaultTplDir = UC_OAUTHDIR . './view/default';
        $this->tplDir = UC_OAUTHDIR . './view/default';
        $this->objDir = UC_DATADIR . './view/oauth2';
        if (!is_dir($this->objDir)) {
            mkdir($this->objDir, 755);
        }
        $this->langFile = UC_ROOT . './view/default/templates.lang.php';
        if (version_compare(PHP_VERSION, '5') == -1) {
            register_shutdown_function(array(&$this, '__destruct'));
        }
    }

    public function assign($k, $v) {
        $this->vars[$k] = $v;
    }

    public function display($file) {
        extract($this->vars, EXTR_SKIP);
        include $this->getTpl($file);
    }

    protected function getTpl($file) {
        isset($_REQUEST['inajax']) && ($file == 'header' || $file == 'footer') && $file = $file . '_ajax';
        isset($_REQUEST['inajax']) && ($file == 'admin_header' || $file == 'admin_footer') && $file = substr($file, 6) . '_ajax';
        $this->tplFile = $this->tplDir . '/' . $file . '.htm';
        $this->objFile = $this->objDir . '/' . $file . '.php';
        $tplfilemtime = @filemtime($this->tplFile);
        if ($tplfilemtime === FALSE) {
            $this->tplFile = $this->defaultTplDir . '/' . $file . '.htm';
        }
        if ($this->force || !file_exists($this->objFile) || @filemtime($this->objFile) < filemtime($this->tplFile)) {
            if (empty($this->language)) {
                @include $this->langFile;
                if (is_array($languages)) {
                    $this->languages += $languages;
                }
            }
            $this->compile();
        }
        return $this->objFile;
    }

    protected function compile() {
        $template = file_get_contents($this->tplFile);
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        $template = preg_replace("/\{lang\s+(\w+?)\}/ise", "\$this->lang('\\1')", $template);

        $template = preg_replace("/\{($this->regexpVar)\}/", "<?=\\1?>", $template);
        $template = preg_replace("/\{($this->regexpConst)\}/", "<?=\\1?>", $template);
        $template = preg_replace("/(?<!\<\?\=|\\\\)$this->regexpVar/", "<?=\\0?>", $template);

        $template = preg_replace("/\<\?=(\@?\\\$[a-zA-Z_]\w*)((\[[\\$\[\]\w]+\])+)\?\>/ies", "\$this->indexArray('\\1', '\\2')", $template);

        $template = preg_replace("/\{\{eval (.*?)\}\}/ies", "\$this->stripVTag('<? \\1?>')", $template);
        $template = preg_replace("/\{eval (.*?)\}/ies", "\$this->stripVTag('<? \\1?>')", $template);
        $template = preg_replace("/\{for (.*?)\}/ies", "\$this->stripVTag('<? for(\\1) {?>')", $template);

        $template = preg_replace("/\{elseif\s+(.+?)\}/ies", "\$this->stripVTag('<? } elseif(\\1) { ?>')", $template);

        for ($i = 0; $i < 2; $i++) {
            $template = preg_replace("/\{loop\s+$this->regexpVTag\s+$this->regexpVTag\s+$this->regexpVTag\}(.+?)\{\/loop\}/ies", "\$this->loopSection('\\1', '\\2', '\\3', '\\4')", $template);
            $template = preg_replace("/\{loop\s+$this->regexpVTag\s+$this->regexpVTag\}(.+?)\{\/loop\}/ies", "\$this->loopSection('\\1', '', '\\2', '\\3')", $template);
        }
        $template = preg_replace("/\{if\s+(.+?)\}/ies", "\$this->stripVTag('<? if(\\1) { ?>')", $template);

        $template = preg_replace("/\{template\s+(\w+?)\}/is", "<? include \$this->getTpl('\\1');?>", $template);
        $template = preg_replace("/\{template\s+(.+?)\}/ise", "\$this->stripVTag('<? include \$this->getTpl(\\1); ?>')", $template);


        $template = preg_replace("/\{else\}/is", "<? } else { ?>", $template);
        $template = preg_replace("/\{\/if\}/is", "<? } ?>", $template);
        $template = preg_replace("/\{\/for\}/is", "<? } ?>", $template);

        $template = preg_replace("/$this->regexpConst/", "<?=\\1?>", $template);

        $template = "<? if(!defined('UC_ROOT')) exit('Access Denied');?>\r\n$template";
        $template = preg_replace("/(\\\$[a-zA-Z_]\w+\[)([a-zA-Z_]\w+)\]/i", "\\1'\\2']", $template);

        $template = preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
        $template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1;?>", $template);

        $fp = fopen($this->objFile, 'w');
        fwrite($fp, $template);
        fclose($fp);
    }

    protected function indexArray($name, $items) {
        $items = preg_replace("/\[([a-zA-Z_]\w*)\]/is", "['\\1']", $items);
        return "<?={$name}{$items}?>";
    }

    protected function stripVTag($s) {
        return preg_replace("/$this->regexpVTag/is", "\\1", str_replace("\\\"", '"', $s));
    }

    protected function loopSection($arr, $k, $v, $statement) {
        $arr = $this->stripVTag($arr);
        $k = $this->stripVTag($k);
        $v = $this->stripVTag($v);
        $statement = str_replace("\\\"", '"', $statement);
        return $k ? "<? foreach((array)$arr as $k => $v) {?>$statement<? }?>" : "<? foreach((array)$arr as $v) {?>$statement<? } ?>";
    }

    protected function lang($k) {
        return !empty($this->languages[$k]) ? $this->languages[$k] : "{ $k }";
    }

    private function _transsid($url, $tag = '', $wml = 0) {
        $sid = $this->sid;
        $tag = stripslashes($tag);
        if (!$tag || (!preg_match("/^(http:\/\/|mailto:|#|javascript)/i", $url) && !strpos($url, 'sid='))) {
            if ($pos = strpos($url, '#')) {
                $urlret = substr($url, $pos);
                $url = substr($url, 0, $pos);
            } else {
                $urlret = '';
            }
            $url .= (strpos($url, '?') ? ($wml ? '&amp;' : '&') : '?') . 'sid=' . $sid . $urlret;
        }
        return $tag . $url;
    }

    public function __destruct() {
        if ($_COOKIE['sid']) {
            
        }
        $sid = rawurlencode($this->sid);
        $searcharray = array(
            "/\<a(\s*[^\>]+\s*)href\=([\"|\']?)([^\"\'\s]+)/ies",
            "/(\<form.+?\>)/is"
        );
        $replacearray = array(
            "\$this->_transsid('\\3','<a\\1href=\\2')",
            "\\1\n<input type=\"hidden\" name=\"sid\" value=\"" . rawurldecode(rawurldecode(rawurldecode($sid))) . "\" />"
        );
        $content = preg_replace($searcharray, $replacearray, ob_get_contents());
        ob_end_clean();
        echo $content;
    }

}
