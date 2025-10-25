<?php

namespace JustChill\LaravelCaptcha\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JustChill\LaravelCaptcha\Services\CaptchaService;

class CaptchaController extends Controller
{
    public function image(Request $request)
    {
        $code = decrypt($request->get('code'));

        session(['laravel_captcha' => [
            'answer' => $code,
            'expires_at' => now()->addMinutes(config('captcha.expires_minutes', 10)),
            'attempts' => 0
        ]]);

        return app(CaptchaService::class)->generateImage($code);
    }


    protected function generateImage($code)
    {
        $width = 150;
        $height = 50;
        $image = imagecreate($width, $height);

        // Colors
        $bg = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $lineColor = imagecolorallocate($image, 200, 200, 200);

        // Add noise lines
        for ($i = 0; $i < 5; $i++) {
            imageline(
                $image,
                rand(0, $width),
                rand(0, $height),
                rand(0, $width),
                rand(0, $height),
                $lineColor
            );
        }

        // Add text
        $fontSize = 20;
        $x = ($width - strlen($code) * $fontSize * 0.6) / 2;
        $y = ($height + $fontSize) / 2;

        imagestring($image, 5, $x, $y - 15, $code, $textColor);

        // Output image
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        imagepng($image);
        imagedestroy($image);
    }
}
