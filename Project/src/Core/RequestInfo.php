<?php

namespace App;
class RequestInfo {
    public $method;
    public $path;
    public $get;
    public $post;
    public $session;
    private $attributes = [];

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->get = $_GET;
        $this->post = $_POST;
        $this->session = &$_SESSION;
    }


    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
    }
    
    public function getAttribute($key, $default = null) {
        return $this->attributes[$key] ?? $default;
    }
}

?>