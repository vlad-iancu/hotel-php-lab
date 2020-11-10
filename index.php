<?php
    require_once './auth/AuthenticationRoutes.php';
    require_once './hotel/identity/HotelIdentityRoutes.php';
    require_once './Router.php';
    $router = new Router();
    $router->authorize = 'authorize';
    addAuthenticationRoutes($router);
    addHotelIdentityRoutes($router);
    $router->route();
  
?>