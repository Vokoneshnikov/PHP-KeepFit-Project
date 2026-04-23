<?php

namespace App\Controllers;

use App\Core\RequestInfo;

class FoodController extends BaseController {
    //TODO DI Container with services
    public function __construct(private RequestInfo $request) {}
    public function index() {}
}
