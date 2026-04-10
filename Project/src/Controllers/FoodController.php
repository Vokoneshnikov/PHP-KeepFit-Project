<?php

namespace App\Controllers;

use App\Core\RequestInfo;

abstract class FoodController extends BaseController {
    private IFoodService $foodService;
    //TODO DI Container with services
    public function __construct(private RequestInfo $request) {}
    public function index() {

    }
}