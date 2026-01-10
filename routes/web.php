<?php

use Illuminate\Support\Facades\Route;

Route::get('/garcom', function () {
    return view('garcom');
});

Route::get('/cozinha', function () {
    return view('cozinha');
});

Route::get('/gerente', function () {
    return view('gerente');
});