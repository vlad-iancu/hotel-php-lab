<?php 
    require_once "./Router.php";
    require_once "./api/rooms/HotelRoomRepository.php";
    function addHotelRoomRoutes(Router $router) {
        $router->post("/hotel_rooms", true, function($body, $userId, $email) {
            $response = array();
            if(!isset($body["id"]) || !$body["id"]) {
                $response = array("status" => "error", "message" => "Field id is not set");
            } else
            $response = getRoomsForHotel($body["id"], $userId);
            echo json_encode($response);
        });
        $router->post("/fetch_room", true, function($body, $userId, $email) {
            $response = array();
            if(!isset($body["id"]) || !is_int($body["id"])) {
                $response = array("status" => "error", "message" => "Field id is not set");
            } else
            $response = getRoomById($userId, $body["id"]);
            echo json_encode($response);
        });
        $router->post("/room", true, function($body, $userId, $email) {
            $response = array();
            if(!isset($body["name"]) || !is_string($body["name"])) {
                $response = array("status" => "error", "message" => "Field name is not set");
            } else
            if(!isset($body["hotel_id"]) || !is_int($body["hotel_id"])) {
                $response = array("status" => "error", "message" => "Field hotel_id is not set");
            } else
            if(!isset($body["price"]) || !is_int($body["price"])) {
                $response = array("status" => "error", "message" => "Field price is not set");
            } else
            $response = addRoomToHotel($userId, $body["hotel_id"], $body["name"], $body["price"]);
            echo json_encode($response);
        });
        
        $router->put("/room", true, function($body, $userId, $email) {
            $response = array();
            if(!isset($body["id"]) || !is_int($body["id"])) {
                $response = array("status" => "error", "message" => "Field id is not set");
            } else
            if(!isset($body["name"]) ||!is_string($body["name"])) {
                $response = array("status" => "error", "message" => "Field name is not set");
            } else
            if(!isset($body["price"]) ||!is_int($body["price"])) {
                $response = array("status" => "error", "message" => "Field price is not set");
            } else
            $response = updateRoom($userId,$body["id"],$body["price"],$body["name"]);
            echo json_encode($response);

        });
        $router->delete("/room", true, function($body, $userId, $email) {
            $response = array();
            if(!isset($body["id"]) || !is_int($body["id"])) {
                $response = array("status" => "error", "message" => "Field id not set");
            } else
            $response = deleteRoom($body["id"]);
            echo json_encode($response);
        });
    }

?>