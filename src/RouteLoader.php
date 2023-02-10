<?php

declare(strict_types=1);

namespace Zxin\Think\Route;

use think\App;
use think\event\RouteLoaded;
use think\Route;
use Zxin\Think\Route\Annotation\Group as GroupAttr;
use Zxin\Think\Route\Annotation\Resource as ResourceAttr;
use Zxin\Think\Route\Annotation\ResourceRule as ResourceRuleAttr;
use Zxin\Think\Route\Annotation\Route as RouteAttr;
use Zxin\Think\Route\Annotation\Middleware as MiddlewareAttr;
use RuntimeException;

class RouteLoader
{
    protected Route $route;

    private array $config = [
        'restfull_definition' => null,
        'route' => [
            'dump_path' => null,
            'only_load_dump' => false,
            'real_time_dump' => true,
        ],
    ];

    public const RESTFULL_DEFINITION = [
        'index'  => ['get', '', 'index'],
        'select' => ['get', '/select', 'select'],
        'read'   => ['get', '/<id>', 'read'],
        'save'   => ['post', '', 'save'],
        'update' => ['put', '/<id>', 'update'],
        // 'patch'  => ['patch', '/<id>', 'patch'],
        'delete' => ['delete', '/<id>', 'delete'],
    ];

    private array $restfullDefinition;

    private string $routeDumpFilename;

    public static function getDumpFilePath(string $filename = 'route_storage.dump.php'): string
    {
        $app = App::getInstance();
        $path = $app->config->get('annotation.route.dump_path') ?: $app->getAppPath();

        $path = str_replace('\\', '/', $path);
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }
        if (!is_dir($path)) {
            throw new RuntimeException("{$path} does not exist");
        }
        return $path . $filename;
    }

    public function __construct(private App $app)
    {
        $this->config = $this->app->config->get('annotation', $this->config);

        $this->routeDumpFilename = self::getDumpFilePath();
    }

    public function registerAnnotation(): void
    {
        if (PHP_VERSION_ID < 80000) {
            return;
        }

        $this->app->event->listen(RouteLoaded::class, function () {
            $this->route = $this->app->route;

            $this->restfullDefinition = $this->config['restfull_definition'] ?: self::RESTFULL_DEFINITION;

            $this->route->rest($this->restfullDefinition, true);

            $this->loadAnnotation();
        });
    }

    public function dataProvider(): array
    {
        if ($this->config['route']['only_load_dump'] ?? false) {
            $filename = $this->routeDumpFilename;

            $this->app->log->debug("load route dump, {$filename}");

            return require $filename;
        } else {
            $rs = new RouteScanning($this->app);

            $this->app->log->debug("scan route annotation");

            $items = $rs->scan();

            if ($this->config['route']['real_time_dump'] ?? false) {
                $this->app->log->debug("route real time dump");

                $dump = new RouteDump($this->routeDumpFilename);
                $dump->saveData($items);
            }

            return $items;
        }
    }

    public function loadAnnotation(): void
    {
        $items = $this->dataProvider();

        foreach ($items as $item) {
            /** @var string $controllerName */
            $controllerName = $item['controller'];
            /** @var GroupAttr|null $groupAttr */
            $groupAttr = $item['group'];
            /** @var array<MiddlewareAttr> $middlewareAttr */
            $middlewareAttr = $item['middleware'];
            /** @var ResourceAttr|null $resourceAttr */
            $resourceAttr = $item['resource'];
            /** @var array<array{method: string, attr: ResourceRuleAttr}> $resourceItems */
            $resourceItems = $item['resourceItems'];
            /** @var array<array{method: string, route: array<RouteAttr>, middleware: array<MiddlewareAttr>}> $routeItems */
            $routeItems = $item['routeItems'];

            $groupCallback = null;

            if ($resourceAttr !== null) {
                $groupCallback = function () use ($controllerName, $resourceAttr, $resourceItems) {
                    // 支持解析扩展资源路由
                    $items = [];
                    foreach ($resourceItems as $item) {
                        $methodName = $item['method'];
                        $rrule      = $item['attr'];
                        //注册路由
                        $nodeName         = $rrule->name ?: $methodName;
                        $items[$nodeName] = [$rrule->method, $nodeName, $methodName];
                    }

                    // 注册资源路由
                    $this->route->rest($items + $this->restfullDefinition, true);
                    $resource = $this->route->resource($resourceAttr->name, $controllerName)
                        ->option($resourceAttr->getOptions());
                    if ($resourceAttr->vars) {
                        $resource->vars($resourceAttr->vars);
                    }
                    if ($resourceAttr->only) {
                        $resource->only($resourceAttr->only);
                    }
                    if ($resourceAttr->except) {
                        $resource->except($resourceAttr->except);
                    }
                    if ($resourceAttr->pattern) {
                        $resource->pattern($resourceAttr->pattern);
                    }
                    $this->route->rest($this->restfullDefinition, true);
                };
            }

            if ($groupAttr && $groupAttr->name) {
                $routeGroup = $this->route->group($groupAttr->name, $groupCallback);
                $routeGroup->option($groupAttr->getOptions());
                if ($groupAttr->pattern) {
                    $routeGroup->pattern($groupAttr->pattern);
                }
            } else {
                $groupCallback && $groupCallback();
                $routeGroup = $this->route->getGroup();
            }
            foreach ($middlewareAttr as $attr) {
                $routeGroup->middleware($attr->name, ...$attr->params);
            }

            foreach ($routeItems as $routeItem) {
                $methodName = $routeItem['method'];
                /** @var MiddlewareAttr[] $middleware */
                $middleware = $routeItem['middleware'];

                foreach ($routeItem['route'] as $routeAttr) {
                    /** @var RouteAttr $routeAttr */

                    //注册路由
                    $nodeName = $routeAttr->name ?: $methodName;

                    if (str_starts_with($nodeName, '/')) {
                        // 根路径
                        $rule = $this->route->rule($nodeName, "{$controllerName}/{$methodName}", $routeAttr->method);
                    } else {
                        $rule = $routeGroup->addRule($nodeName, "{$controllerName}/{$methodName}", $routeAttr->method);
                    }

                    $rule->option($routeAttr->getOptions());
                    foreach ($middleware as $item) {
                        $rule->middleware($item->name, ...$item->params);
                    }

                    if ($routeAttr->setGroup) {
                        $rule->group($routeAttr->setGroup);
                    }
                    if ($routeAttr->pattern) {
                        $rule->pattern($routeAttr->pattern);
                    }
                }
            }
        }
    }
}
