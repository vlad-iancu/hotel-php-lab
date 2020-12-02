<?php
require_once "./Credentials.php";
function getMysqliConnection()
{
    $conn = mysqli_connect("localhost", MYSQL_USER, MYSQL_PASSWORD, MYSQL_SCHEMA, MYSQL_PORT);
    if(!$conn) {
        $result = array(
            "status" => 500,
            "message" => "Could not connect to the database (getMysqliConnection)"
        );
        exit(json_encode($result));
    }
    return $conn;
}
?>
