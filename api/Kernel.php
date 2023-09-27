<?php


$routes = [];
include 'routes.php';
include 'vendor/autoload.php';

$templatesDir = '../templates';




class Request{
    private $uri;
    private $method;       // HTTP method (GET, POST, PUT, DELETE, etc.)
    private $headers;      // HTTP headers as an associative array
    private $query;        // Query parameters as an associative array (e.g., ?param1=value1&param2=value2)
    private $formData;     // Form data from POST requests as an associative array
    private $routeParams;  // Route parameters extracted from the URL
    private $body;         // Request body (for POST and PUT requests)

    public function __construct()
    {
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->headers = getallheaders();
        $this->query = $_GET;
        $this->formData = $_POST;
        $this->parseRouteParams();
        $this->parseRequestBody();
    }

    public function getUri(){
        return $this->uri;
    }
    public function getMethod(){
        return $this->method;
    }
    public function getHeaders(){
        return $this->headers;
    }
    public function getQueryParams(){
        return $this->query;
    }
    public function getFormData(){
        return $this->formData;
    }
    public function getRouteParams(){
        return $this->routeParams;
    }
    public function getBody(){
        return $this->body;
    }
    public function parseRouteParams(){
        // this is where you define a pattern to search inside the URI
        // and then return the params inside so that it may be pass to something else
        $this->routeParams = ['id' => "1234"];
    }
    public function parseRequestBody(){
        // handle the POST or PUT methods and set the body of them
        // im not going to use this for now
        $this->body = "Remember to change this!";
    }
}




class Response{

    private $content;
    private $statusCode;
    private $headers;

    // represents an HTTP response to the user
    public function __construct($content = '', $statusCode = 200, $headers = []){
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function setContent($content){
        $this->content = $content;
    }
    public function setStatusCode($statusCode){
        $this->statusCode = $statusCode;
    }
    public function setHeaders($headers){
        $this->headers = $headers;
    }

    public function send(){

        // set the headers in the response
        foreach ($this->headers as $header){
            header($header);
        }

        http_response_code($this->statusCode);

        // echo the body, like html or json or anything else
        echo $this->content;
    }

    public static function template($templateName, $templateArguments = []){
        // init Twig
        $loader = new \Twig\Loader\FilesystemLoader($GLOBALS['templatesDir']);
        $twig = new \Twig\Environment($loader);

        // load the twig template
        $template = $twig->load($templateName);
        return $template->render($templateArguments);
    }
}





class Route{
    protected $controller;
    protected $action;
    protected $method;
    protected $middleware;

    public function __construct($method, $controller, $action, $middleware=null){
        $this->method = $method;
        $this->controller = $controller;
        $this->action = $action;
        $this->middleware = $middleware;
    }
    public function getController(){
        return $this->controller;
    }
    public function getAction(){
        return $this->action;
    }
    public function getMethod(){
        return $this->method;
    }
    public function setMiddleware($middleware){
        echo $middleware;
        $this->middleware = $middleware;
    }
    public function getMiddleware(){
        return $this->middleware;
    }
}





// using GLOBALS variables it's easier to check the routes
// and to keep track of them
class Router{

    // these variables are only used on group routes
    public $routes = [];
    protected $prefix;

    public function get($uri, $controllerAction){
        [$controller, $action] = explode('@', $controllerAction);
        # echo ($this->prefix? $this->prefix.$uri : $uri)."<br>";
        #echo $this->prefix."<br>";
        $this->routes['GET'][($this->prefix? $this->prefix.$uri : $uri)] = new Route('GET', $controller, $action);
    }

    public function post($uri, $controllerAction){
        [$controller, $action] = explode('@', $controllerAction);
        $this->routes['POST'][($this->prefix? $this->prefix.$uri : $uri)] = new Route('POST', $controller, $action);
    }

    public function group($prefix, $callback, $middleware=null){

        // creates a new self instance to register the inside routes
        $router = new self();

        // adds the last prefix of the last group to the new router
        $router->prefix = $this->prefix ? $this->prefix.$prefix : $prefix;

        // register the routes inside the group
        $callback($router);
        return $router;
    }

    public function setMiddleware($middleware){
        // echo "<pre>";
        // print_r($GLOBALS['router']);
        // echo "</pre>";
        foreach($this->routes as $route => $route_index){
            $method = $route->getMethod();
            $GLOBALS['routes'][$method][$route_index]->setMiddleware($middleware);
        }
    }

    public static function route(Request $request){
        // get the method from the request
        $method = $request->getMethod();
        $uri = parse_url($request->getUri(), PHP_URL_PATH);

        // check if the route exists
        if (isset($GLOBALS['router']->routes[$method][$uri])){
            return $GLOBALS['router']->routes[$method][$uri];
        }

        // in case the route doesn't exist it returns null
        return null;
    }
}






class Kernel{

    public function handle(Request $request){
        // handle the request
        $response = $this->handleRequest($request);
        // return the response to the client
        return $response->send();
    }

    protected function handleRequest($request){
        // send the request to the right controller
        $route = $this->routeRequest($request);

        // if the route does not exist it returns a 404 Response
        if ($route instanceof Response)
            return $route;
        $request = $this->handleMiddleware($request, $route);

        // create instance of the controller
        $controller = $this->createController($route);

        // call the action of the controller and obtain a response
        $response = $this->callAction($controller, $route->getAction(), $request);

        // return the response to the client
        return $response;
    }

    protected function handleMiddleware(Request $request, Route $route){
        echo $route->getMiddleware();
#        [$middleware, $action] = explode('@', $route->getMiddleware());

        // if(gettype($middleware) != NULL){
        //     echo "Hello";
        //     return $this->callAction($middleware, $action, $request);
        // }
        return $request;
    }
    protected function routeRequest(Request $request){

        // Extracts the routing information from the request
        $route = Router::route($request);

        // in case the route doesn't exist
        if (!$route){
            // creates a 404 response
            $response = new Response('404 Not found', 404);

            // returns the response to the client
            return $response;
        }

        return $route;
    }

    protected function createController(Route $route){
        // create an instance of the controller based on the route
        $controllerName = $route->getController();
        return new $controllerName;
    }

    protected function callAction($controller, $action, Request $request){
        // pass the action to the controller as method
        // and the request as an argument
        return $controller->$action($request);
    }
}

function loadDirectory($directory){
    foreach (scandir($directory) as $File)
    {
        if ($File!= '.' && $File!= '..'){
            require $directory.'/'.$File;
        }
    }
}

// load the Controllers
loadDirectory("../Controllers");

// load middlewares
loadDirectory("../Middlewares");