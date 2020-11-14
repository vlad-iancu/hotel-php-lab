<?php 
    require_once "./Router.php";
    function addHotelRoomRoutes(Router $router) {
        $router->get("/rooms", true, function($body, $userId) {

        });
        $router->get("/room", true, function($body, $userId) {

        });
        $router->post("/room", true, function($body, $userId) {
            $response = array();
            if(!isset($body["name"]) || !is_string($body["name"])) {
                $response = array("status" => "error", "message" => "Field name is not set");
            }
            if(!isset($body["hotel_id"]) || !is_int($body["hotel_id"])) {
                $response = array("status" => "error", "message" => "Field hotel_id is not set");
            }
            if(!isset($body["price"]) || !is_int($body["price"])) {
                $response = array("status" => "error", "message" => "Field price is not set");
            }
            $response = addRoomToHotel($userId, $body["hotel_id"], $body["name"], $body["price"]);
            echo json_encode($response);
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