<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

// Laravel requires an APP_KEY before encryption services can boot. Generate an
// ephemeral key for each test run instead of storing a key-shaped value in the
// repository. Dotenv is immutable in Laravel, so this value is not overwritten.
$testKey = 'base64:'.base64_encode(random_bytes(32));

putenv('APP_KEY='.$testKey);
$_ENV['APP_KEY'] = $testKey;
$_SERVER['APP_KEY'] = $testKey;
