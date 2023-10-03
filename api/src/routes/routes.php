<?php

// init the router to register the routes
$router = new Router();

$router->get('/', 'HomeController@index');