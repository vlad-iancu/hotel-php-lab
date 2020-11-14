<?php 
    require_once './Router.php';
    require_once './api//hotel/identity/HotelIdentityRepository.php';
    
    function addHotelIdentityRoutes(Router $router) {

        $router->get("/hotel", true, function($body, $userId, $email) {
            
        });
        $router->delete("/hotel", true, function($body, $userId, $email) {

        });
        $router->post("/hotel", true, function($body, $userId, $email) {
            $response = array();
            if(!isset($userId) || $userId <= 0) {
                http_response_code(401);
                $response = array("status" => "error", "message" => "Unauthorized");
            }
            if(!isset($body["name"])) {
                http_response_code(400);
                $response = array("status" => "error", "message" => "Field name not set");
            }

            $response = createHotel($userId, $body["name"]);
            echo json_encode($response); 
        });

        $router->put("/rename_hotel", true, function($body, $userId, $email) {
            $response = array();
            if(!isset($body["new_name"]) || is_string($body["new_name"])) {
                http_response_code(400);
                $response = array("status" => "error", "message" => "Field new_name not set");
            }
            if(!isset($body["id"]) || !is_int($body["id"])) {
                http_response_code(400);
                $response = array("status" => "error", "message" => "Field id not set");
            }
            if(!isset($userId) || $userId <= 0) {
                http_response_code(401);
                $response = array("status" => "error", "message" => "Unauthorized");
            }
            $response = renameHotel($userId, $body["id"], $body["new_name"]);
            echo json_encode($response);

        });

        $router->post("/hotel_admin", true, function($body, $userId, $email) {
            $response = array();
            if(!isset($body["email"]) || !$body["email"]) {
                $response = array("status" => "error", "message" => "Field email not set");
            }
            if(!isset($body["hotel_id"]) || !$body["hotel_id"]) {
                $response  =array("status" => "error", "message" => "Field hotel_id not set");
            }
            $response = addHotelAdmin($userId, $body["hotel_id"], $body["email"]);
            echo json_encode($response);
        });

        $router->put("/hotel_visibility", true, function($body, $userId, $email) {
            $response = array();
            if(!isset($body["visibility"]) || !$body["visibility"]) {
                $response = array("status" => "error", "message" => "Field visibility not set");
            }
            if(!isset($body["hotel_id"]) || !$body["hotel_id"]) {
                $response = array("status" => "error", "message" => "Field hotel_id not set");
            }
            $response = setHotelVisibility($userId, $body["hotel_id"], $body["visibility"]);
            echo json_encode($response);
        });

        $router->get("/user_hotels", true, function($userId, $email) {
            echo json_encode(getHotelsForUser($email));
        });
    }

?>