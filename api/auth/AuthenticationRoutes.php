<?php 
    require_once './Router.php';
    require_once './api/auth/AuthenticationRepository.php';
    function addAuthenticationRoutes(Router $router) {
        //$router->get("/home", function() {
        //    echo "Home endpoint";
        //});
        //$router->get("/contact", function($userId) {
        //    $contact = array();
        //    $contact["name"]= "Vlad";
        //    $contact["email"] = "myemail@mail.com";
        //    echo json_encode($contact);
        //});
        $router->get("/user", true, function($userId, $email) {
            echo json_encode(getUser($userId));
        });
        
        $router->post("/register", false, function($body) {
            $body = json_decode(file_get_contents("php://input"), true);
            //echo getPasswordHash($body["password"]);
            if($body == null) return;
            $response = register($body["user_name"], $body["email"], getPasswordHash($body["password"]));
            echo json_encode($response, 3);
            //echo json_last_error_msg();
        });
        $router->post("/login", false, function($body) {
            $response = login($body["email"], $body["password"]);
            echo json_encode($response, 3);
        });
        $router->post("/refresh", false, function($body) {
            $response = refresh($body["refreshToken"]);
            echo json_encode($response, 3);
        });
        $router->delete("/user", false, function($body) {
            $response = deleteUser($body["email"], $body["password"]);
            echo json_encode($response);
        });
        $router->put("/change_username", false, function($body) {
            $response = changeUserName($body["email"], $body["password"], $body["user_name"]);
            echo json_encode($response);
        });
    }

?>