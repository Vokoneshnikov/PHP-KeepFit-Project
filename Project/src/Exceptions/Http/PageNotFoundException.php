<?php

namespace App\Exceptions\Http;

use App\Exceptions\AppException;

class PageNotFoundException extends AppException
{
    protected int $statusCode = 404;

    public function getHttpCode(): int
    {
        return $this->statusCode;
    }
}
