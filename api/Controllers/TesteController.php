<?php


class TesteController{

    public function index(Request $request){
        return new Response(Response::template('teste.twig'), 200);
    }
    public function info(Request $request){
        return new Response("Hello info");
    }
}