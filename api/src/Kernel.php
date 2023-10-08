<?php


$routes = [];
include '../routes/routes.php';
include '../../vendor/autoload.php';

$templatesDir = '../templates';


class Method {
    public $method;
    public function __construct($method){
        $this->method = $method;
    }
}



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
        $this->query = $this->loadRequestData($_GET);
        $this->formData = $this->loadRequestData($_POST);
        // $this->parseRouteParams();
        // $this->parseRequestBody();
    }

    protected function loadRequestData($method){
        $params = new Method($_SERVER['REQUEST_METHOD']);
        foreach($method as $index => $data){
            $params->$index = $data;
        }
        // echo "<pre>";
        // print_r($params);
        // echo "</pre>";
        return $params;
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
    // not necessary
    // public function parseRouteParams(){
    //     // this is where you define a pattern to search inside the URI
    //     // and then return the params inside so that it may be pass to something else
    //     $this->routeParams = ['id' => "1234"];
    // }

    // not necesary right now
    // public function parseRequestBody(){
    //     // handle the POST or PUT methods and set the body of them
    //     // im not going to use this for now
    //     $this->body = "Remember to change this!";
    // }
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
    protected $uri;
    protected $methods;
    protected $middleware;
    protected $pattern;

    public function __construct($methods, $uri, $controller, $action, $middleware=null, $pattern=''){
        $this->controller = $controller;
        $this->action = $action;
        $this->middleware = $middleware;
        $this->uri = $uri;
        $this->methods = $this->loadMethods($methods);
        $this->pattern = $pattern;
    }
    protected function loadMethods($methods){
        $response_methods = array();
        $methods = is_array($methods) ? $methods : [$methods];
        foreach($methods as $method){
            $response_methods[$method] = $this->controller."@".$this->action;
        }
        return $response_methods;
    }
    public function getController(){
        [$this->controller, $this->action] = explode('@', $this->methods[$_SERVER["REQUEST_METHOD"]]);
        return $this->controller;
    }
    public function getAction(){
        return $this->action;
    }
    public function getMethods(){
        return array_keys($this->methods);
    }
    public function getMethod($method){
        return $this->methods[$method];
    }
    public function getUri(){
        return $this->uri;
    }
    public function getPattern(){
        return $this->pattern;
    }
    public function setMiddleware($middleware){
        $this->middleware = $middleware;
    }
    public function getMiddleware(){
        return $this->middleware;
    }
    public function addMethod($method, $controller, $action){
        $this->methods[$method] = $controller."@".$action;
    }
}



// using GLOBALS variables it's easier to check the routes
// and to keep track of them
class Router{

    // these variables are only used on group routes
    public $routes = [];
    protected $prefix;

    public function get($uri, $controllerAction){
        $this->registerRoute($uri, $controllerAction, 'GET');
    }

    public function post($uri, $controllerAction){
        $this->registerRoute($uri, $controllerAction, 'POST');
    }

    protected function registerRoute($uri, $controllerAction, $method, $middleware=null){
        [$controller, $action] = explode('@', $controllerAction);
        // separate the uri and the regex pattern
        [$pattern, $uri] = [$uri, rtrim(preg_replace('/[.\\^$*+?()[\]{}|\\\\]/', '', $uri), '/')];

        // get the route if exists
        $uri_prefix = ($this->prefix? $this->prefix.$uri : $uri);

        // check if the route exists
        if(isset($this->routes[$uri_prefix]))

            // add the method to the route
            $this->routes[$uri_prefix]->addMethod($method, $controller, $action);

        // create the route
        else
            $this->routes[($this->prefix? $this->prefix.$uri : $uri)] = new Route($method, $uri, $controller, $action, $middleware, $pattern);
    }

    public function group($prefix, $callback, $middleware=null){

        // creates a new self instance to register the inside routes
        $router = new self();

        // adds the last prefix of the last group to the new router
        $router->prefix = $this->prefix ? $this->prefix.$prefix : $prefix;

        // register the routes inside the group
        $callback($router);

        // iterate trough the router routes and register them on the main router
        foreach($router->routes as $uri => $route){
            // set the middleware if exists
            $route->setMiddleware($middleware);
            $this->routes[$uri] = $route;
        }

        // returns the router for future implementation
        return $router;
    }

    public static function route(Request $request){
        // get the method from the request
        $uri = parse_url($request->getUri(), PHP_URL_PATH);

        // stores the route
        foreach($GLOBALS['router']->routes as $route => $routeObject){

            // converts the route uri into a pattern
            $pattern = $routeObject->getPattern();
            $pattern = '^'.$pattern.'?$^';

            preg_match($pattern, $request->getUri(), $matches);
        }
        // echo "<pre>";
        // remove the regex patterns and the last / of the uri
        $uri = rtrim(str_replace($matches[1], '', $uri), '/');

        // check if the route exists
        if (!isset($GLOBALS['router']->routes[$uri]))
            // in case the route doesn't exist it returns null
            return null;

        // get the route
        $route = $GLOBALS['router']->routes[$uri];
        $request->matches = $matches[1];
        return $route;
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
        // assigns a request to the registered controller
        $route = $this->routeRequest($request);

        // if the route does not exist it returns a 404 Response
        if ($route instanceof Response)
            return $route;

        $request = $this->handleMiddleware($request, $route);

        // if the middleware responds with a Response like 401, or any other responses
        // returns the reponse made by it
        if ($request instanceof Response)
            return $request;

        if (!$this->verifyMethod($request, $route)){
            return new Response('Method not allowed', 405);
        }

        // create instance of the controller
        $controller = $this->createController($route);

        // call the action of the controller and obtain a response
        $response = $this->callAction($controller, $route->getAction(), $request);

        // return the response to the client
        return $response;
    }

    protected function handleMiddleware(Request $request, Route $route){
        // get the middleware
        $middleware = $route->getMiddleware();

        // verify if the route has a middleware
        if (!$middleware)
            // returns the request
            return $request;

        // separeate the middleware and the action
        [$middleware, $action] = explode('@', $middleware);

        // create an instance of a middleware
        $middleware = $this->createMiddleware($middleware);

        // call the middleware and the action
        return $this->callAction($middleware, $action, $request);
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

    protected function verifyMethod(Request $request, Route $route){
        // confirm if the route allows the method requested
        return in_array($request->getMethod(), $route->getMethods());
    }

    protected function createController(Route $route){
        // create an instance of the controller based on the route
        $controllerName = $route->getController();
        return new $controllerName;
    }

    protected function createMiddleware($middleware){
        // create an instance of the middleware
        return new $middleware;
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
            include $directory.'/'.$File;
        }
    }
}

// load Docker engine
loadDirectory('../Docker');

// load the Controllers
loadDirectory("../Controllers");

// load middlewares
loadDirectory("../Middlewares");