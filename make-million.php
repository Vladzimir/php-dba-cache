<?php
if (PHP_SAPI != 'cli') {
    die('no trespass! call the administrator!');
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

set_time_limit(0);
ini_set("memory_limit", "-1");

function generateAddressFixture()
{
    return array(
        // Random string with length between 8 and 16
        substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, rand(8, 16)),
        // Random five digit number
        sprintf('%05d', rand(1, 99999)),
        // Random string with length between 8 and 16
        substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, rand(8, 16)),
        // Random string with length 2
        substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 2)
    );
}

$db = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'app.db4';
$cache = new \PhpDbaCache\Cache($db, 'db4', 'c-', true);

for ($key = 0; $key < 1000000; $key++) {
    print_r($value = generateAddressFixture());
    print 'SAVED=' . (int)$cache->put($key, $value, rand(1, 21600)) . PHP_EOL;
}

$cache->closeDba();

die('END');
