<?php

namespace App\Core;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $path,
        public array $httpMethods = [],
    ) {
    }
}
