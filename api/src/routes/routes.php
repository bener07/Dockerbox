<?php

// init the router to register the routes
$router = new Router();

$router->get('/', 'HomeController@index');
$router->get('/info', 'HomeController@index');
$router->get('/docker(/.+)', 'HomeController@index');
$router->post('/docker(/.+)', 'HomeController@index');