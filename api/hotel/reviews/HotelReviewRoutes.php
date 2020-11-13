<?php 
    require_once './Router.php';
    function addHotelReviewRoutes(Router $router) {
        $router->get("/hotel_reviews", true, function($body, $userId) {

        });
        $router->post("/review_hotel", true, function($body, $userId) {

        });
        $router->delete("/review_hotel", true, function($body, $userId) {

        });
        $router->get("/stars", true, function($body, $userId) {

        });
        $router->get("/search_hotels", true, function($body, $userId) {

        });
    }

?>