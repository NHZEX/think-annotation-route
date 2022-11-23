#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace Zxin\Think\Validate;

use Exception;
use think\App;
use Zxin\Think\Route\RouteDump;
use function file_exists;

foreach ([__DIR__.'/../../../autoload.php', __DIR__.'/../../autoload.php', __DIR__.'/../autoload.php', __DIR__.'/vendor/autoload.php', __DIR__.'/../vendor/autoload.php'] as $_) {
    if (file_exists($_)) {
        require $_;
        $file = $_;
        break;
    }
}
if (!isset($file)) {
    throw new Exception('autoload.php file does not exist');
}

App::getInstance()->initialize();

RouteDump::dump();