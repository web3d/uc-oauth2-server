<?php

namespace TimeCheer\UCServer\OAuth2\Server\Base;

/**
 * 将原来的模板引擎复制过来,重构
 */
class Template
{

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

    public function __construct()
    {
        ob_start();
        $this->defaultTplDir = UC_OAUTHDIR . './view/default';
        $this->tplDir = UC_OAUTHDIR . './view/default';
        $this->objDir = UC_DATADIR . './view/oauth2';
        if (!is_dir($this->objDir)) {
            mkdir($this->objDir);
        }
        $this->langFile = UC_ROOT . './view/default/templates.lang.php';
        if (version_compare(PHP_VERSION, '5') == -1) {
            register_shutdown_function(array(&$this, '__destruct'));
        }
    }

    public function assign($k, $v)
    {
        $this->vars[$k] = $v;
    }

    public function display($file)
    {
        extract($this->vars, EXTR_SKIP);
        include $this->getTpl($file);
    }

    protected function getTpl($file)
    {
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

    protected function compile()
    {
        $template = file_get_contents($this->tplFile);
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        $template = preg_replace_callback("/\{lang\s+(\w+?)\}/is", array($this, 'complie_callback_lang_1'), $template);

        $template = preg_replace("/\{($this->regexpVar)\}/", "<?=\\1?>", $template);
        $template = preg_replace("/\{($this->regexpConst)\}/", "<?=\\1?>", $template);
        $template = preg_replace("/(?<!\<\?\=|\\\\)$this->regexpVar/", "<?=\\0?>", $template);

        $template = preg_replace_callback("/\<\?=(\@?\\\$[a-zA-Z_]\w*)((\[[\\$\[\]\w]+\])+)\?\>/is", array($this, 'complie_callback_arrayindex_12'), $template);

        $template = preg_replace_callback("/\{\{eval (.*?)\}\}/is", array($this, 'complie_callback_stripvtag_1'), $template);
        $template = preg_replace_callback("/\{eval (.*?)\}/is", array($this, 'complie_callback_stripvtag_1'), $template);
        $template = preg_replace_callback("/\{for (.*?)\}/is", array($this, 'complie_callback_stripvtag_for1'), $template);

        $template = preg_replace_callback("/\{elseif\s+(.+?)\}/is", array($this, 'complie_callback_stripvtag_elseif1'), $template);

        for ($i = 0; $i < 2; $i++) {
            $template = preg_replace_callback("/\{loop\s+$this->regexpVTag\s+$this->regexpVTag\s+$this->regexpVTag\}(.+?)\{\/loop\}/is", array($this, 'complie_callback_loopsection_1234'), $template);
            $template = preg_replace_callback("/\{loop\s+$this->regexpVTag\s+$this->regexpVTag\}(.+?)\{\/loop\}/is", array($this, 'complie_callback_loopsection_123'), $template);
        }
        $template = preg_replace_callback("/\{if\s+(.+?)\}/is", array($this, 'complie_callback_stripvtag_if1'), $template);

        $template = preg_replace("/\{template\s+(\w+?)\}/is", "<? include \$this->gettpl('\\1');?>", $template);
        $template = preg_replace_callback("/\{template\s+(.+?)\}/is", array($this, 'complie_callback_stripvtag_template1'), $template);


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

    protected function complie_callback_lang_1($matches)
    {
        return $this->lang($matches[1]);
    }

    protected function complie_callback_arrayindex_12($matches)
    {
        return $this->arrayindex($matches[1], $matches[2]);
    }

    protected function complie_callback_stripvtag_1($matches)
    {
        return $this->stripvtag('<? ' . $matches[1] . '?>');
    }

    protected function complie_callback_stripvtag_for1($matches)
    {
        return $this->stripvtag('<? for(' . $matches[1] . ') {?>');
    }

    protected function complie_callback_stripvtag_elseif1($matches)
    {
        return $this->stripvtag('<? } elseif(' . $matches[1] . ') { ?>');
    }

    protected function complie_callback_loopsection_1234($matches)
    {
        return $this->loopsection($matches[1], $matches[2], $matches[3], $matches[4]);
    }

    protected function complie_callback_loopsection_123($matches)
    {
        return $this->loopsection($matches[1], '', $matches[2], $matches[3]);
    }

    protected function complie_callback_stripvtag_if1($matches)
    {
        return $this->stripvtag('<? if(' . $matches[1] . ') { ?>');
    }

    protected function complie_callback_stripvtag_template1($matches)
    {
        return $this->stripvtag('<? include $this->gettpl(' . $matches[1] . '); ?>');
    }

    protected function arrayindex($name, $items)
    {
        $items = preg_replace("/\[([a-zA-Z_]\w*)\]/is", "['\\1']", $items);
        return "<?=$name$items?>";
    }

    protected function stripvtag($s)
    {
        return preg_replace("/$this->vtag_regexp/is", "\\1", str_replace("\\\"", '"', $s));
    }

    protected function loopsection($arr, $k, $v, $statement)
    {
        $arr = $this->stripvtag($arr);
        $k = $this->stripvtag($k);
        $v = $this->stripvtag($v);
        $statement = str_replace("\\\"", '"', $statement);
        return $k ? "<? foreach((array)$arr as $k => $v) {?>$statement<? }?>" : "<? foreach((array)$arr as $v) {?>$statement<? } ?>";
    }

    protected function lang($k)
    {
        return !empty($this->languages[$k]) ? $this->languages[$k] : "{ $k }";
    }

    protected function _transsid($url, $tag = '', $wml = 0)
    {
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

    protected function destruct_callback_transsid_312($matches)
    {
        return $this->_transsid($matches[3], '<a' . $matches[1] . 'href=' . $matches[2]);
    }

    public function __destruct()
    {
        if ($_COOKIE['sid']) {
            
        }
        $sid = rawurlencode($this->sid);
        $content = preg_replace_callback("/\<a(\s*[^\>]+\s*)href\=([\"|\']?)([^\"\'\s]+)/is", array($this, 'destruct_callback_transsid_312'), ob_get_contents());
        $content = preg_replace("/(\<form.+?\>)/is", "\\1\n<input type=\"hidden\" name=\"sid\" value=\"" . rawurldecode(rawurldecode(rawurldecode($sid))) . "\" />", $content);
        ob_end_clean();
        echo $content;
    }

}
