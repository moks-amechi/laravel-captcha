<?php

namespace JustChill\LaravelCaptcha\Services;

use Illuminate\Support\Str;

class CaptchaService
{
    protected $sessionKey = 'laravel_captcha';

    public function generate(?string $type = null)
    {
        $type = $type ?? config('captcha.type', 'math');
        $challenge = $this->createChallenge($type);
        session([$this->sessionKey => [
            'answer' => $challenge['answer'],
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0
        ]]);

        return $challenge;
    }

    public function validate($userAnswer)
    {
        $captcha = session($this->sessionKey);

        if (!$captcha) {
            return false;
        }

        if (now()->greaterThan($captcha['expires_at'])) {
            session()->forget($this->sessionKey);
            return false;
        }

        if ($captcha['attempts'] >= 5) {
            session()->forget($this->sessionKey);
            return false;
        }

        // Increment attempts
        session([
            $this->sessionKey . '.attempts' => $captcha['attempts'] + 1
        ]);

        /** if we want it not to be case sensitive, we would uncomment this and comment the other one */
        // $isValid = strtolower(trim($userAnswer)) === strtolower(trim($captcha['answer']));
        $isValid = trim($userAnswer) === trim($captcha['answer']);


        if ($isValid) {
            session()->forget($this->sessionKey);
        }

        return $isValid;
    }

    public function render(?string $type = null)
    {
        $type = $type ?? config('captcha.type', 'math');
        $challenge = $this->generate($type);

        return view('captcha::challenge', [
            'challenge' => $challenge,
            'hasGD' => extension_loaded('gd')
        ])->render();
    }


    protected function createChallenge($type)
    {
        switch ($type) {
            case 'math':
                return $this->mathChallenge();
            case 'word':
                return $this->wordChallenge();
            case 'image':
                return $this->imageChallenge();
            default:
                return $this->mathChallenge();
        }
    }

    protected function mathChallenge()
    {
        $operations = ['+', '-', '*'];
        $operation = $operations[array_rand($operations)];

        switch ($operation) {
            case '+':
                $a = rand(1, 20);
                $b = rand(1, 20);
                $answer = $a + $b;
                $question = "What is {$a} + {$b}?";
                break;
            case '-':
                $a = rand(10, 30);
                $b = rand(1, $a);
                $answer = $a - $b;
                $question = "What is {$a} - {$b}?";
                break;
            case '*':
                $a = rand(1, 10);
                $b = rand(1, 10);
                $answer = $a * $b;
                $question = "What is {$a} × {$b}?";
                break;
        }

        return [
            'type' => 'math',
            'question' => $question,
            'answer' => (string) $answer
        ];
    }

    protected function generateRandomWord(int $length = 6): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $word = '';

        for ($i = 0; $i < $length; $i++) {
            $char = $characters[rand(0, 25)];

            // Randomly upper or lowercase it
            $word .= rand(0, 1) ? strtoupper($char) : $char;
        }

        return $word;
    }

    protected function wordChallenge()
    {
        $length = config('captcha.length.word', 6);
        $word = $this->generateRandomWord($length);

        return [
            'type' => 'word',
            'question' => "Type the word: <strong>{$word}</strong>",
            'answer' => $word
        ];
    }

    protected function imageChallenge()
    {
        if (!extension_loaded('gd')) {
            // Fallback to math challenge with a warning
            $mathChallenge = $this->mathChallenge();
            $mathChallenge['warning'] = 'Image CAPTCHA requires GD extension. Please install php-gd or use text-based CAPTCHA.';
            return $mathChallenge;
        }
        $length = config('captcha.length.image', 5);
        $code = Str::random($length);
        return [
            'type' => 'image',
            'question' => 'Enter the code shown in the image',
            'answer' => $code,
            'image_url' => route('captcha.image', ['code' => encrypt($code)])
        ];
    }

    public function generateImage(): \Illuminate\Http\Response
    {
        $code = decrypt(request()->query('code'));

        $width = config('captcha.image.width', 150);
        $height = config('captcha.image.height', 50);
        $fontSize = config('captcha.image.font_size', 24);
        $bgColor = config('captcha.image.bg_color', '#ffffff');
        $textColor = config('captcha.image.text_color', '#000000');
        $fonts = config('captcha.fonts');

        $image = imagecreatetruecolor($width, $height);

        // Convert hex colors to RGB
        [$r, $g, $b] = sscanf($bgColor, "#%02x%02x%02x");
        $bg = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, $bg);

        [$tr, $tg, $tb] = sscanf($textColor, "#%02x%02x%02x");
        $textColorAlloc = imagecolorallocate($image, $tr, $tg, $tb);

        // Add noise lines
        if (config('captcha.image.noise', true)) {
            for ($i = 0; $i < config('captcha.image.lines', 3); $i++) {
                $lineColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
                imageline($image, 0, rand(0, $height), $width, rand(0, $height), $lineColor);
            }
        }

        // Draw text
        $font = $fonts[0] ?? null;
        if ($font && file_exists($font)) {
            $box = imagettfbbox($fontSize, 0, $font, $code);
            $x = ($width - ($box[2] - $box[0])) / 2;
            $y = ($height + ($box[1] - $box[7])) / 2;

            imagettftext($image, $fontSize, 0, $x, $y, $textColorAlloc, $font, $code);
        } else {
            imagestring($image, 5, 10, 10, $code, $textColorAlloc);
        }

        // Capture output
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return response($imageData)->header('Content-Type', 'image/png');
    }
}
