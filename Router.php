<?php 
    class Router {
        public $routes = array();
        public $routeCount = 0;
        
        public function route() {
            $path = $_SERVER["REQUEST_URI"];
            foreach($this->routes as $rt) {
                if($rt->method == $_SERVER["REQUEST_METHOD"])
                    if($rt->path == $_SERVER["REQUEST_URI"]) {
                        error_log("Route $rt->path matches");
                        $invokeBlock = $rt->block;
                        $invokeBlock();
                    }
                    else {
                        error_log("Route $rt->path does not match current request");
                    }
                else {
                    error_log("Route $rt->path does not match current request");
                }
            }
        }

        public function get($path, $block) {
            $newRoute = new Route();
            $newRoute->path = $path;
            $newRoute->method = "GET";
            $newRoute->block = $block;
            $this->routes[$this->routeCount] = $newRoute;
            $this->routeCount++;
        }

        public function post($path, $block) {
            $newRoute = new Route();
            $newRoute->path = $path;
            $newRoute->method = "POST";
            $newRoute->block = $block;
            $this->routes[$this->routeCount] = $newRoute;
            $this->routeCount++;
        }
        
    }
    //I made this router kinda stupid, it can only match equality
    //This won't matter anyway because there is no such thing like
    // '/user/{id}' because all the data will be sent and received via JSON
    // because this is an API that does serve HTML only through '/' route
    //All the others will be just REST endpoints

    class Route {
        public $path;
        public $block;
        public $method;
    }
?>