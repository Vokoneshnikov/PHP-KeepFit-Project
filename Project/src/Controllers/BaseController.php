<?php

namespace App\Controllers;

abstract class BaseController {
    public abstract function index(RequestInfo $request);
}