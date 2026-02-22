<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;


class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Welcome to the AkoCloud API',
            'version' => '1.0.0'
        ], 200, ['Content-Type' => 'application/json'], JSON_PRETTY_PRINT);
    }
}