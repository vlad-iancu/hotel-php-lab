<?php 
function getMysqliConnection() {
    return mysqli_connect(gethostbyname(gethostname()), "local", "MySql2020-Remote-Password#", "hotel_db" );
}
?>