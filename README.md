# Laravel CAPTCHA

⚠️ **Beta Version** - This package is under active development. Use in production at your own risk.

**Ultra-easy CAPTCHA integration for Laravel 9, 10, 11, and 12.**

`justchill/laravel-captcha` provides a minimal, flexible CAPTCHA system with math, word, and image rendering, session-based validation, Blade directive support, and optional middleware integration.

---

## 📋 Requirements

- PHP ^8.0
- Laravel ^9.0|^10.0|^11.0|^12.0
- GD Extension (optional, for image CAPTCHA)

---

## 🚀 Features

- ✅ Laravel 9–12 support
- 🧠 Three CAPTCHA types: Math, Word, and Image
- 🔒 Session-based validation with expiration
- 🧩 Simple Blade directive: `@captcha`
- 🔒 Middleware enforcement
- 🔍 Built-in validation rule: `captcha`
- 🖼️ Image-based CAPTCHA with GD support
- ⚙️ Configurable attempts limit and expiration
- 🛠️ Publishable config + views
- ✔️ Case-sensitive validation (configurable)

---

## 📦 Installation

### Via Composer

```bash
composer require justchill/laravel-captcha
```

### Local Development (Path Repository)

Add to your `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../laravel-captcha",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "justchill/laravel-captcha": "*"
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

Then run:

```bash
composer require justchill/laravel-captcha
```

### Install GD Extension (for Image CAPTCHA)

**Ubuntu/Debian:**
```bash
sudo apt-get install php-gd
sudo service apache2 restart  # or php-fpm
```

**CentOS/RHEL:**
```bash
sudo yum install php-gd
sudo systemctl restart httpd
```

**Verify installation:**
```bash
php -m | grep gd
```

---

## ⚙️ Configuration

Publish configuration and views:

```bash
php artisan vendor:publish --tag=captcha-config
```

This creates:
- `config/captcha.php` - Main configuration
- `resources/views/vendor/captcha/challenge.blade.php` - Customizable view

### Configuration File (`config/captcha.php`)

```php
return [
    'type' => env('CAPTCHA_TYPE', 'math'), // Options: math, word, image
    
    'expires_minutes' => 10,
    'max_attempts' => 5,
    
    'allowed_chars' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
    
    'length' => [
        'word' => 6,
        'image' => 5,
    ],
    
    'fonts' => [
        base_path('vendor/justchill/laravel-captcha/src/fonts/Roboto-Bold.ttf'),
    ],
    
    'image' => [
        'width' => 150,
        'height' => 50,
        'font_size' => 24,
        'bg_color' => '#ffffff',
        'text_color' => '#000000',
        'noise' => true,
        'lines' => 3,
    ],
    
    'math_difficulty' => 'easy',
];
```

---

## 🧪 Usage

### Basic Blade Integration

Simply add the `@captcha` directive to your form:

```blade
<form method="POST" action="/submit">
    @csrf
    
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    
    @captcha
    
    <button type="submit">Submit</button>
</form>
```

### Alternative: Include View

```blade
@include('captcha::challenge')
```

### With Custom Type

```blade
{!! app('captcha')->render('image') !!}
```

---

## ✅ Validation

### Using Validation Rule

```php
public function store(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|min:8',
        'captcha' => 'required|captcha',
    ]);
    
    // Process form...
}
```

### Using Middleware

Protect routes with CAPTCHA middleware:

```php
Route::post('/register', [RegisterController::class, 'store'])
    ->middleware('captcha');

Route::group(['middleware' => 'captcha'], function () {
    Route::post('/contact', [ContactController::class, 'send']);
    Route::post('/comment', [CommentController::class, 'store']);
});
```

### Manual Validation

```php
use JustChill\LaravelCaptcha\Facades\Captcha;

if (Captcha::validate($request->input('captcha'))) {
    // CAPTCHA is valid
} else {
    // CAPTCHA is invalid
}
```

---

## 🎨 CAPTCHA Types

### 1. Math CAPTCHA (Default)

Simple arithmetic questions:

```env
CAPTCHA_TYPE=math
```

Example: "What is 15 + 7?"

### 2. Word CAPTCHA

Random character strings:

```env
CAPTCHA_TYPE=word
```

Example: "Type the word: **aBc3Ef**"

### 3. Image CAPTCHA

Visual text rendering (requires GD extension):

```env
CAPTCHA_TYPE=image
```

---

## 🔧 Advanced Configuration

### Change CAPTCHA Type Per Form

```php
// In your controller
public function showForm()
{
    $challenge = app('captcha')->generate('image');
    
    return view('form', compact('challenge'));
}
```

```blade
<!-- In your view -->
@include('captcha::challenge', ['challenge' => $challenge])
```

### Customize Math Difficulty

Edit `config/captcha.php`:

```php
'math_difficulty' => 'easy', // or 'medium', 'hard'
```

### Case-Insensitive Validation

Edit `CaptchaService.php` line 52:

```php
// Case-sensitive (default)
$isValid = trim($userAnswer) === trim($captcha['answer']);

// Case-insensitive
$isValid = strtolower(trim($userAnswer)) === strtolower(trim($captcha['answer']));
```

### Custom Styling

Publish views and edit `resources/views/vendor/captcha/challenge.blade.php`:

```bash
php artisan vendor:publish --tag=captcha-config
```

---

## 🔍 Complete Form Example

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Contact Form</title>
    <style>
        form { max-width: 500px; margin: 50px auto; }
        input, textarea { width: 100%; padding: 10px; margin-bottom: 15px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; }
        .error { color: red; font-size: 14px; }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('contact.send') }}">
        @csrf
        
        <div>
            <label>Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required>
            @error('name') <span class="error">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label>Message</label>
            <textarea name="message" rows="5" required>{{ old('message') }}</textarea>
            @error('message') <span class="error">{{ $message }}</span> @enderror
        </div>
        
        @captcha
        
        <button type="submit">Send Message</button>
    </form>
</body>
</html>
```

**Controller:**

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
            'captcha' => 'required|captcha',
        ]);
        
        // Send email or process form...
        
        return back()->with('success', 'Message sent successfully!');
    }
}
```

---

## 🐛 Troubleshooting

### Image CAPTCHA Not Showing?

**Problem:** Blank image or error when using image CAPTCHA.

**Solution:** Install GD extension:

```bash
# Check if GD is installed
php -m | grep gd

# Install GD (Ubuntu/Debian)
sudo apt-get install php8.2-gd  # Replace 8.2 with your PHP version

# Restart web server
sudo service apache2 restart
```

### Session Not Persisting?

**Problem:** CAPTCHA validation fails even with correct answer.

**Solution:** Check your session configuration in `.env`:

```env
SESSION_DRIVER=file  # or database, redis
SESSION_LIFETIME=120
```

Clear config cache:

```bash
php artisan config:clear
php artisan cache:clear
```

### "Too Many Attempts" Error

**Problem:** Validation fails after 5 attempts.

**Solution:** Adjust `max_attempts` in `config/captcha.php` or wait for session to expire (default: 10 minutes).

### CAPTCHA Expired Error

**Problem:** Session expires before user submits form.

**Solution:** Increase `expires_minutes` in `config/captcha.php`:

```php
'expires_minutes' => 15, // Changed from 10
```

---

## 🗺️ Roadmap

- [ ] PHPUnit test coverage
- [ ] Audio CAPTCHA (accessibility)
- [ ] Refresh button for image CAPTCHAs
- [ ] Custom difficulty levels for all types
- [ ] Multi-language support
- [ ] Redis/database storage option
- [ ] Rate limiting per IP

---

## 🧑‍💻 Contributing

Pull requests are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please follow Laravel coding conventions and include test coverage where possible.

---

## 📞 Support

- 🐛 **Report bugs:** [GitHub Issues](https://github.com/justchill/laravel-captcha/issues)
- 💬 **Questions:** [GitHub Discussions](https://github.com/justchill/laravel-captcha/discussions)
- 📧 **Email:** help@justchill.ng

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

## 👏 Credits

Created with ❤️ by **abram.CataLYST** for **JustChill Webcreative**

---

## ⭐ Star Us!

If you find this package helpful, please give it a star on GitHub!

```

Made with ♥ in Nigeria
```
