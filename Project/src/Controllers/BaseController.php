<?php

namespace App\Controllers;

use App\Core\RequestInfo;

abstract class BaseController {
    private RequestInfo $request;
    public abstract function index();
}