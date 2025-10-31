<?php
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// load environment variables
if (class_exists('Dotenv\Dotenv')) {
    $rootDir = dirname(__DIR__);

    // Prefer .env.test if it exists, otherwise fall back to .env
    $envFile = file_exists("$rootDir/.env.test") ? '.env.test' : '.env';

    $dotenv = Dotenv\Dotenv::createImmutable($rootDir, $envFile);
    $dotenv->load();
}

// ensure tests are not running on a production database
if (getenv('APP_ENV') !== 'test') {
    fwrite(STDERR, "Tests should only be run in the test environment (APP_ENV=test)\n");
}
