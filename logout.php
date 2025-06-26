<?php
error_log("----- LOGOUT --------------------------");

session_start(["cookie_samesite" => "lax"]);
error_log("SESSION " . session_id() . ": " . print_r($_SESSION, 1));
session_destroy();

echo "logged out";
?>

<p>
    <a href="/">/index</a><br />
    <a href="/logout.php">/logout</a>
</p>
