<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="AirBNB API",
 *     version="1.2.4"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller
{
    //
}
