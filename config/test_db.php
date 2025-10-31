<?php
$db = require __DIR__ . '/db.php';
// test database! Important not to run tests on production or development databases
$db['dsn'] = getenv('TEST_DB_DSN') ?: 'mysql:host=db;dbname=storyvault_test';

return $db;
