<?php
    require_once './api/auth/AuthenticationRoutes.php';
    require_once './api/hotel/identity/HotelIdentityRoutes.php';
    require_once './api/rooms/HotelRoomRoutes.php';
    require_once './Router.php';
    $router = new Router();
    $router->authorize = 'authorize';
    addAuthenticationRoutes($router);
    addHotelIdentityRoutes($router);
    addHotelRoomRoutes($router);
    $router->route();
?>