<?php

namespace TimeCheer\UCServer\OAuth2\Server\Base;

use OAuth2\Storage\AuthorizationCodeInterface;
use OAuth2\Storage\AccessTokenInterface;
use OAuth2\Storage\ClientCredentialsInterface;
use OAuth2\Storage\UserCredentialsInterface;
use OAuth2\Storage\RefreshTokenInterface;
use PDO;
use InvalidArgumentException;

/**
 * 适配与uc_server的storage提供者
 */
class Storage implements AuthorizationCodeInterface, AccessTokenInterface, ClientCredentialsInterface, UserCredentialsInterface, RefreshTokenInterface {

    protected $db;
    protected $config;

    public function __construct($connection) {

        if (!is_array($connection)) {
            throw new InvalidArgumentException('First argument to OAuth2_Storage must be an instance of PDO or a configuration array');
        }
        if (!isset($connection['dsn'])) {
            throw new InvalidArgumentException('configuration array must contain "dsn"');
        }
        // merge optional parameters
        $connection = array_merge(array(
            'username' => null,
            'password' => null,
            'tablePrefix' => ''
                ), $connection);

        $this->config = array(
            'client_table' => $connection['tablePrefix'] . 'applications',
            'access_token_table' => $connection['tablePrefix'] . 'oauth_access_tokens',
            'refresh_token_table' => $connection['tablePrefix'] . 'oauth_refresh_tokens',
            'code_table' => $connection['tablePrefix'] . 'oauth_authorization_codes',
            'user_table' => $connection['tablePrefix'] . 'members',
        );
        $this->db = new PDO($connection['dsn'], $connection['username'], $connection['password']);

        // debugging
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     *  OAuth2_Storage_ClientCredentialsInterface 
     */
    public function checkClientCredentials($client_id, $client_secret = null) {
        $stmt = $this->db->prepare(sprintf('SELECT authkey AS client_secret from %s where appid = :client_id AND is_deleted = 0', $this->config['client_table']));
        $stmt->execute(compact('client_id'));
        $result = $stmt->fetch();

        // make this extensible
        return $result['client_secret'] == $client_secret;
    }

    /**
     * 
     * @param type $client_id
     * @return array
     * @see \OAuth2\Storage\ClientInterface
     */
    public function getClientDetails($client_id) {
        $stmt = $this->db->prepare(sprintf('SELECT redirect_uri, is_mobile, scope, user_id from %s where appid = :client_id AND is_deleted = 0', $this->config['client_table']));//, grant_types
        $stmt->execute(compact('client_id'));

        return $stmt->fetch();
    }

    /**
     * 应用定义可以提供的授权类型 //TODO
     * @param string $client_id
     * @param string $grant_type
     * @return boolean
     */
    public function checkRestrictedGrantType($client_id, $grant_type) {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            return in_array($grant_type, (array) $details['grant_types']);
        }

        // if grant_types are not defined, then none are restricted
        return true;
    }

    /**
     *  OAuth2_Storage_AccessTokenInterface 
     */
    public function getAccessToken($access_token) {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s where access_token = :access_token', $this->config['access_token_table']));

        $token = $stmt->execute(compact('access_token'));
        if ($token = $stmt->fetch()) {
            // convert date string back to timestamp
            $token['expires'] = strtotime($token['expires']);
        }

        return $token;
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null) {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
            $stmt = $this->db->prepare(sprintf('UPDATE %s SET client_id=:client_id, expires=:expires, user_id=:user_id, scope=:scope where access_token=:access_token', $this->config['access_token_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (access_token, client_id, expires, user_id, scope) VALUES (:access_token, :client_id, :expires, :user_id, :scope)', $this->config['access_token_table']));
        }
        return $stmt->execute(compact('access_token', 'client_id', 'user_id', 'expires', 'scope'));
    }

    /* OAuth2_Storage_AuthorizationCodeInterface */

    public function getAuthorizationCode($code) {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s where authorization_code = :code', $this->config['code_table']));
        $stmt->execute(compact('code'));

        if ($code = $stmt->fetch()) {
            // convert date string back to timestamp
            $code['expires'] = strtotime($code['expires']);
        }

        return $code;
    }

    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null) {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        // if it exists, update it.
        if ($this->getAuthorizationCode($code)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET client_id=:client_id, user_id=:user_id, redirect_uri=:redirect_uri, expires=:expires, scope=:scope where authorization_code=:code', $this->config['code_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (authorization_code, client_id, user_id, redirect_uri, expires, scope) VALUES (:code, :client_id, :user_id, :redirect_uri, :expires, :scope)', $this->config['code_table']));
        }
        return $stmt->execute(compact('code', 'client_id', 'user_id', 'redirect_uri', 'expires', 'scope'));
    }

    public function expireAuthorizationCode($code) {
        $stmt = $this->db->prepare(sprintf('DELETE FROM %s WHERE authorization_code = :code', $this->config['code_table']));

        return $stmt->execute(compact('code'));
    }

    /** OAuth2_Storage_UserCredentialsInterface */
    public function checkUserCredentials($username, $password) {
        if ($user = $this->getUser($username)) {
            return $this->checkPassword($user, $password);
        }
        return false;
    }

    public function getUserDetails($username) {
        return $this->getUser($username);
    }

    /* OAuth2_Storage_RefreshTokenInterface */

    public function getRefreshToken($refresh_token) {
        $stmt = $this->db->prepare(sprintf('SELECT * FROM %s WHERE refresh_token = :refresh_token', $this->config['refresh_token_table']));

        $token = $stmt->execute(compact('refresh_token'));
        if ($token = $stmt->fetch()) {
            // convert expires to epoch time
            $token['expires'] = strtotime($token['expires']);
        }

        return $token;
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null) {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        $stmt = $this->db->prepare(sprintf('INSERT INTO %s (refresh_token, client_id, user_id, expires, scope) VALUES (:refresh_token, :client_id, :user_id, :expires, :scope)', $this->config['refresh_token_table']));

        return $stmt->execute(compact('refresh_token', 'client_id', 'user_id', 'expires', 'scope'));
    }

    public function unsetRefreshToken($refresh_token) {
        $stmt = $this->db->prepare(sprintf('DELETE FROM %s WHERE refresh_token = :refresh_token', $this->config['refresh_token_table']));

        return $stmt->execute(compact('refresh_token'));
    }

    // plaintext passwords are bad!  Override this for your application
    public function checkPassword($user, $password) {
        return $user['password'] == md5(md5($password) . $user['salt']);
    }

    public function getUser($username) {
        $stmt = $this->db->prepare($sql = sprintf('SELECT uid AS user_id, username, password, salt from %s where username=:username', $this->config['user_table']));
        $stmt->execute(array('username' => $username));
        return $stmt->fetch();
    }

    public function setUser($username, $password, $firstName = null, $lastName = null) {
        // if it exists, update it.
        if ($this->getUser($username)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET password=:password, where username=:username', $this->config['user_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (username, password) VALUES (:username, :password)', $this->config['user_table']));
        }
        return $stmt->execute(compact('username', 'password', 'firstName', 'lastName'));
    }

    public function getClientScope($client_id) {
        $details = $this->getClientDetails($client_id);

        return isset($details['scope']) ? $details['grant_types'] : '';
    }

    public function isPublicClient($client_id) {
        return false;
    }

}
