<?php

use Illuminate\Support\Facades\Route;
use JustChill\LaravelCaptcha\Http\Controllers\CaptchaController;

Route::get('captcha/image', [CaptchaController::class, 'image'])->name('captcha.image');
