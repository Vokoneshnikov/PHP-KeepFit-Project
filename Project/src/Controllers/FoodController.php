<?php

namespace App\Controllers;

use App\Core\RequestInfo;

abstract class FoodController extends BaseController {
    private IFoodService $foodService;
    //TODO DI Container with services
    private RequestInfo $request;
    public function __construct() {}
    public function index() {

    }
}