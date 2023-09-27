<?php

class InfoMiddleware{
    public function lock(Request $request){
        echo 'Middleware';
        return $request;
    }
}