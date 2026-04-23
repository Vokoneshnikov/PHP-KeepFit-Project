<?php

namespace App\Controllers;

use App\Core\RequestInfo;

abstract class BaseController {
    public function __construct(private RequestInfo $request) {}
    public abstract function index();
}
