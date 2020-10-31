<?php
    //readfile("./ui/index.html")
    require_once './auth/AuthenticationRoutes.php';
    require_once './UIRoutes.php';
    require_once './Router.php';
    $router = new Router();
    addAuthenticationRoutes($router);
    addUiRoutes($router);
    $router->get("/path", function() {
        readfile("./page/index.html");
    });
    $router->route();
    //$conn = getMysqliConnection();
    //mysqli_close($conn);
    //echo "mysql local successful";
    //echo "<br>Random token:" . uuid();
    //echo "<br>Password hash of \'vlad\' " . getPasswordHash("vlad") . " " . getPasswordHash("vlad");
?>
