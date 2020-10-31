<?php 
    require_once './Router.php';
    require_once './auth/AuthenticationRepository.php';
    function addAuthenticationRoutes($router) {
        $router->get("/home", function() {
            echo "Home endpoint";
        });
        $router->get("/contact", function() {
            $contact = array();
            $contact["name"]= "Vlad";
            $contact["email"] = "myemail@mail.com";
            echo json_encode($contact);
        });
        $router->get("/info", function() {
            phpinfo();
        });
        $router->post("/register", function() {
            $body = json_decode(file_get_contents("php://input"), true);
            //echo getPasswordHash($body["password"]);
            if($body == null) return;
            $response = register($body["user_name"], $body["email"], getPasswordHash($body["password"]));
            echo json_encode($response, 3);
            //echo json_last_error_msg();
        });
        $router->post("/login", function() {
            $body = json_decode(file_get_contents("php://input"), true);
            $response = login($body["email"], $body["password"]);
            echo json_encode($response, 3);
        });
        $router->post("/refresh", function() {
            $body = json_decode(file_get_contents("php://input"), true);
            $response = refresh($body["refreshToken"]);
            echo $body["refreshToken"]."\n";
            echo json_encode($response, 3);
        });
    }

?>