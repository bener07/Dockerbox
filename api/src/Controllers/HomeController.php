<?php


class HomeController {
    public function index(Request $request){
        $engine = new Engine('http://engine:2376');
        $engine->send($request->matches);
        $engine->close();
        return new Response($engine->response);
    }
}