<?php
    require_once './api/auth/AuthenticationRoutes.php';
    require_once './api/hotel/identity/HotelIdentityRoutes.php';
    require_once './Router.php';
    $router = new Router();
    $router->authorize = 'authorize';
    addAuthenticationRoutes($router);
    addHotelIdentityRoutes($router);
    $router->route();
  
?>