<?php 
    require_once "./Router.php";
    function addHotelRoomRoutes(Router $router) {
        $router->get("/rooms", true, function($body, $userId) {

        });
        $router->get("/room", true, function($body, $userId) {

        });
        $router->post("/room", true, function($body, $userId) {

        });
        $router->put("/room", true, function($body, $userId) {

        });
        $router->delete("/room", true, function($body, $userId) {

        });
        $router->post("/book_room", true, function($body, $userId) {

        });
        $router->get("/book_room", true, function($body, $userId) {

        });
        $router->delete("/book_room", true, function($body, $userId) {

        });
        $router->get("/bookings", true, function($body, $userId) {

        });

    }

?>