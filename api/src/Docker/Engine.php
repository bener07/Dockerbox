<?php


class Engine{

    protected $socket;
    protected $communication;
    public $response;

    public function __construct($socket){
        $this->socket = $socket;
        $this->communication = curl_init();
        return $this;
    }

    public function endpoint($endpoint){
        $this->socket = $this->socket.$endpoint;
        return $this;
    }

    public function getSocket(){
        return $this->socket;
    }

    private function setopt($opt, $value){
        curl_setopt($this->communication, $opt, $value);
        return $this;
    }

    // send the request
    public function send($endpoint=null){
        // sets the endpoint before sending it
        $this->setopt(CURLOPT_URL, $this->socket.$endpoint);

        // enables the curl instance to return the web page
        $this->setopt(CURLOPT_RETURNTRANSFER, true);

        // sends the response and saves it
        $this->response = curl_exec($this->communication);

        return $this;
    }

    // build the get request
    public function get($data, $endpoint=null){

        // in case a endpoint is provided it sets it
        $this->endpoint($endpoint);

        // set the method to GET
        $this->setopt(CURLOPT_CUSTOMREQUEST, 'GET');

        // set GET data
        $this->setopt(CURLOPT_POSTFIELDS, $data);
        return $this;
    }

    // build the post request
    public function post($data, $endpoint=null){

        $this->endpoint($endpoint);

        // encode the array
        $payload = json_encode($data);

        // set the post data
        $this->setopt(CURLOPT_POSTFIELDS, $payload);

        // set the headers to send json data
        $this->setopt(CURLOPT_HTTPHEADERS, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));

        return $this;
    }

    // close the connection
    public function close(){
        return curl_close($this->communication);
    }
}
