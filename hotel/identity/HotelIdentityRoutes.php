<?php 
    require_once './Router.php';
    require_once './hotel/identity/HotelIdentityRepository.php';
    
    function addHotelIdentityRoutes(Router $router) {

        $router->get("/hotel", true, function($body, $userId) {

        });
        $router->delete("/hotel", true, function($body, $userId) {

        });
        $router->post("/hotel", true, function($body, $userId) {
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
        $router->put("/rename_hotel", true, function($body, $userId) {
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
        $router->put("/add_hotel_admin", true, function($body, $userId) {

        });
        $router->put("/hotel_visibility", true, function($body, $userId) {

        });
        $router->post("/create_worker_group", true, function($body, $userId) {

        });
        $router->put("/add_worker_to_group", true, function($body, $userId) {

        });
        $router->put("/remove_worker_from_group", true, function($body, $userId) {

        });
        $router->delete("/remove_worker_group", true, function($body, $uesrId) {

        });
    }

?>