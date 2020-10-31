<?php 
    require_once './Router.php';
    function addUiRoutes($router) {
        $router->get("/", function() {
            readfile("./ui/index.html");
        });
    }

?>