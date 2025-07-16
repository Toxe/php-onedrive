<?php
require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/src/router.php');

session_start(["cookie_samesite" => "lax"]);

route()->output();
