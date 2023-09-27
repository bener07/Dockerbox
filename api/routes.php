<?php

// init the router to register the routes
$router = new Router();

$router->get('/', 'HomeController@index');
$router->get('/teste', 'TesteController@index');
$router->group('/info', function ($router){
    $router->get('', 'TesteController@info');
})->setMiddleware('InfoMiddleware@lock');