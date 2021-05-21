<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return response([
        'app_name' => 'api.andeanwide.com',
        'version' => '2.0.0',
        'contact' => 'soporte@andeanwide.com'
    ]);
});
