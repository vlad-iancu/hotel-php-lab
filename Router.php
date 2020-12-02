<?php
class Router
{
    public $routes = array();
    public $routeCount = 0;
    public $authorize;
    public $name = "";

    public function route()
    {
        $path = strtok($_SERVER["REQUEST_URI"], "?");
        $params = array();
        $found = false;
        $matchingRoute = null;
        //echo $_SERVER["REQUEST_URI"];
        foreach ($this->routes as $rt) {
            if ($rt->method == $_SERVER["REQUEST_METHOD"]) {
                if ($rt->path == $path) {
                    if ($found) {
                        $this->notFound();
                        return;
                    } else {
                        $matchingRoute = $rt;
                        $found = true;
                    }
                }
            }
        }
        if (!$found) {
            $this->notFound();
            return;
        }
        $invokeBlock = $matchingRoute->block;
        if ($matchingRoute->authorized) {
            if ($matchingRoute->method != "GET") {
                $body = json_decode(file_get_contents("php://input"), true);
                $cred = ($this->authorize)();
                $invokeBlock($body, $cred["userId"], $cred["email"]);
            } else {
                error_log("Router matched the request: ".$_SERVER["REQUEST_URI"]. " as a GET authorized request");
                $cred = ($this->authorize)();
                $invokeBlock($cred["userId"], $cred["email"]);
            }
        } else {
            if ($matchingRoute->method != "GET") {
                $body = json_decode(file_get_contents("php://input"), true);
                $invokeBlock($body);
            } else {
                $invokeBlock($body);
            }
        }
    }

    public function get($path, $authorize, $block)
    {
        $newRoute = new Route();
        $newRoute->path = $path;
        $newRoute->method = "GET";
        $newRoute->authorized = $authorize;
        $newRoute->block = $block;
        $this->routes[$this->routeCount] = $newRoute;
        $this->routeCount++;
    }

    public function post($path, $authorize, $block)
    {
        $newRoute = new Route();
        $newRoute->path = $path;
        $newRoute->method = "POST";
        $newRoute->authorized = $authorize;
        $newRoute->block = $block;
        $this->routes[$this->routeCount] = $newRoute;
        $this->routeCount++;
    }

    public function delete($path, $authorize, $block)
    {
        $newRoute = new Route();
        $newRoute->path = $path;
        $newRoute->method = "DELETE";
        $newRoute->authorized = $authorize;
        $newRoute->block = $block;
        $this->routes[$this->routeCount] = $newRoute;
        $this->routeCount++;
    }

    public function put($path, $authorize, $block)
    {
        $newRoute = new Route();
        $newRoute->path = $path;
        $newRoute->method = "PUT";
        $newRoute->authorized = $authorize;
        $newRoute->block = $block;
        $this->routes[$this->routeCount] = $newRoute;
        $this->routeCount++;
    }

    public function notFound()
    {
        $result = array("status" => "error", "message" => "There is no route matching this url");
        echo json_encode($result);
    }

    public function multipleMatch()
    {
        $result = array("status" => "error", "message" => "There are multiple routes matching this URL");
        echo json_encode($result);
    }
}
//I made this router kinda stupid, it can only match equality
//This won't matter anyway because there is no such thing like
// '/user/{id}' because all the data will be sent and received via JSON
// because this is an API that does serve HTML only through '/' route
//All the others will be just REST endpoints

class Route
{
    public $path;
    public $block;
    public $method;
    public $authorized;
}
