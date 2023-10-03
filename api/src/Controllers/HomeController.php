<?php

class HomeController {
    public function index(Request $request){
        return new Response("Yello");
    }
}