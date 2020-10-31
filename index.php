<?php
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
?>
