<?php
require_once "./Credentials.php";
function getMysqliConnection()
{
    return mysqli_connect("localhost", MYSQL_USER, MYSQL_PASSWORD, MYSQL_SCHEMA, MYSQL_PORT);
}
?>
