<?php
/**
 * TOKEN服务
 */

require __DIR__ . '/_init.php';

$ctrl = new \TimeCheer\UCServer\OAuth2\Server\Controller\AccessTokenController();
$ctrl->index();