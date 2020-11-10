<?php 
    require_once './Router.php';
    function addUiRoutes($router) {
        $router->get("/", false, function($body, $userId) {
            readfile("./ui/index.html");
        });
    }
?>