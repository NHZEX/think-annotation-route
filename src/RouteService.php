<?php

declare(strict_types=1);

namespace Zxin\Think\Route;

use think\Service;

class RouteService extends Service
{
    /**
     *
     */
    private RouteLoader $loader;

    public function register()
    {
    }

    public function boot()
    {
        $this->loader = $this->app->make(RouteLoader::class);

        $this->loader->registerAnnotation();
    }
}
