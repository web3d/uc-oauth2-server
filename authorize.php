<?php

require __DIR__ . '/_init.php';

$ctrl = new \TimeCheer\UCServer\OAuth2\Server\Controller\AuthorizeController();
$ctrl->index();
exit;
