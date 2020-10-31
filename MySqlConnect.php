<?php 
require_once "./Credentials.php";
function getMysqliConnection() {
    return mysqli_connect(gethostbyname(gethostname()), MYSQL_USER, MYSQL_PASSWORD, MYSQL_SCHEMA);
}
?>