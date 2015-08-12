<?php

/**
 * 系统初始化
 */
class Bootstrap {
    
    const GRANTTYPE_CODE = 'authorization_code';
    const GRANTTYPE_CLIENT = 'client_credentials';
    const GRANTTYPE_REFRESH = 'refresh_token';
    const GRANTTYPE_PWD = 'password';
    
    /**
     *
     * @var OAuth2_Server server对象
     */
    protected static $oauth2Server;
    
    /**
     *
     * @var OAuth2_Storage_UCenter
     */
    protected static $oauth2Storage;

    /**
     * 执行初始化 将原来在入口文件index.php或admin.php文件中做的初始化过程封装到此处
     */
    public static function init() {
        /**
         * oauth2系统根目录
         */
        define('UC_OAUTHDIR', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
        
        /**
         * 兼容uc_server所在目录
         */
        !defined('UC_ROOT') && define('UC_ROOT', dirname(UC_OAUTHDIR) . DIRECTORY_SEPARATOR);
        
        !defined('UC_DATADIR') && define('UC_DATADIR', UC_ROOT . 'data' . DIRECTORY_SEPARATOR);
        /**
         * oauth2所在目录的绝对url
         */
        define('STATIC_URL', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/')));
        
        require_once UC_ROOT . 'release/release.php';
        require_once UC_DATADIR . 'config.inc.php';

        if (constant('UC_DEBUG')) {
            ini_set('display_errors', 1);
            error_reporting(E_ERROR);
        } else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }
        
        require_once UC_OAUTHDIR . 'lib/vendor/OAuth2/Autoloader.php';
        
        OAuth2_Autoloader::register();
    }
    
    /**
     * 运行oauth服务容器，并且需要指定鉴权类型
     * 每调用一次，会增加一种鉴权方式
     * @param string $grantType 传空值代表不初始化鉴权类型
     * @return OAuth2_Server
     */
    public static function runOAuth2Server($grantType = self::GRANTTYPE_CODE) {
        
        self::getStorage();
        
        if (!self::$oauth2Server instanceof OAuth2_Server) {
            self::$oauth2Server = new OAuth2_Server(self::$oauth2Storage);
        }
        
        if ($grantType) {
            self::$oauth2Server->addGrantType(self::_initGranter($grantType));
        }
        
        return self::$oauth2Server;
    }
    
    public static function getStorage() {
        //TODO 优化加载
        if (!class_exists('OAuth2_Storage_UCenter', false)) {
            require_once(UC_OAUTHDIR . 'lib/OAuth2/Storage/UCenter.php');
        }
        
        if (!self::$oauth2Storage instanceof OAuth2_Storage_UCenter) {
            self::$oauth2Storage = new OAuth2_Storage_UCenter(array(
                    'dsn' => 'mysql:dbname='.UC_DBNAME.';host='.UC_DBHOST, 
                    'username' => UC_DBUSER, 
                    'password' => UC_DBPW));
        }
        
        return self::$oauth2Storage;
    }
    
    /**
     * 创建鉴权对象
     * @param string $grantType
     * @return \OAuth2_GrantType_AuthorizationCode|mixed
     */
    private static function _initGranter($grantType) {
        switch ($grantType) {
            case self::GRANTTYPE_CLIENT:
                $granter = new OAuth2_GrantType_ClientCredentials(self::$oauth2Storage);
                break;
            
            case self::GRANTTYPE_REFRESH:
                $granter = new OAuth2_GrantType_RefreshToken(self::$oauth2Storage);
                break;
            
            case self::GRANTTYPE_PWD:
                $granter = new OAuth2_GrantType_UserCredentials(self::$oauth2Storage);
                break;

            default:
                $granter = new OAuth2_GrantType_AuthorizationCode(self::$oauth2Storage);
                break;
        }
        
        return $granter;
    }
}
