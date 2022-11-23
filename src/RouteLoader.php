<?php
declare(strict_types=1);

namespace Zxin\Think\Route;

use ReflectionClass;
use think\App;
use think\event\RouteLoaded;
use think\Route;
use Zxin\Think\Route\Annotation\Group as GroupAttr;
use Zxin\Think\Route\Annotation\Resource as ResourceAttr;
use Zxin\Think\Route\Annotation\ResourceRule as ResourceRuleAttr;
use Zxin\Think\Route\Annotation\Route as RouteAttr;
use Zxin\Think\Route\Annotation\Middleware as MiddlewareAttr;
use function array_map;
use function str_replace;
use function str_starts_with;

class RouteLoader
{
    private App $app;

    protected Route $route;

    private array $config = [
        'restfull_definition' => null,
    ];

    const RESTFULL_DEFINITION = [
        'index'  => ['get', '', 'index'],
        'select' => ['get', '/select', 'select'],
        'read'   => ['get', '/<id>', 'read'],
        'save'   => ['post', '', 'save'],
        'update' => ['put', '/<id>', 'update'],
        // 'patch'  => ['patch', '/<id>', 'patch'],
        'delete' => ['delete', '/<id>', 'delete'],
    ];

    private array $restfullDefinition;

    public function __construct(App $app)
    {
        $this->app = $app;

        $this->config = $this->app->config->get('annotation', $this->config);
    }

    public function registerAnnotation(): void
    {
        if (PHP_VERSION_ID < 80000) {
            return;
        }

        $this->app->event->listen(RouteLoaded::class, function () {

            //            $dumpData = require __DIR__ . '/route.php';

            $this->route = $this->app->route;

            $this->restfullDefinition = $this->config['restfull_definition'] ?: self::RESTFULL_DEFINITION;

            $this->route->rest($this->restfullDefinition, true);

            $this->loadAnnotation();
        });
    }

    public function loadAnnotation(): void
    {
        $rs = new RouteScanning($this->app);
        foreach ($rs->scan() as $item) {
            /** @var string $class */
            $class = $item['class'];
            /** @var string $controllerName */
            $controllerName = $item['controller'];
            /** @var GroupAttr $groupAttr */
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

            if ($resourceAttr) {
                $groupCallback = function () use ($class, $controllerName, $resourceAttr, $resourceItems) {

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
            if ($middlewareAttr) {
                foreach ($middlewareAttr as $attr) {
                    $routeGroup->middleware($attr->name, ...$attr->params);
                }
            }

            $groupCallback = null;

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

    public function parseAnnotation(string $class, ReflectionClass $refClass, ?GroupAttr $groupAttr)
    {
        // todo 按组提前排序

        $groupCallback = null;

        // $reader      = new AnnotationReader();

        // 资源路由
        $attr = $refClass->getAttributes(ResourceAttr::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if ($attr) {
            /** @var ResourceAttr $resourceAttr */
            $resourceAttr  = $attr->newInstance();
            $groupCallback = function () use ($class, $resourceAttr, $refClass) {

                // 支持解析扩展资源路由
                $items = [];
                foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $refMethod) {
                    $rule = $refMethod->getAttributes(ResourceRuleAttr::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
                    if (empty($rule)) {
                        continue;
                    }
                    /** @var ResourceRuleAttr $rrule */
                    $rrule = $rule->newInstance();

                    //注册路由
                    $nodeName = $rrule->name ?: $refMethod->getName();

                    $items[$nodeName] = [$rrule->method, $nodeName, $refMethod->getName()];
                }

                // 注册资源路由
                $this->route->rest($items + $this->restfullDefinition, true);
                $resource = $this->route->resource($resourceAttr->name, $class)
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

        $groupCallback = null;

        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $refMethod) {
            //中间件
            /** @var MiddlewareAttr[] $middleware */
            $middleware = array_map(
                fn($attr) => $attr->newInstance(),
                $refMethod->getAttributes(MiddlewareAttr::class, \ReflectionAttribute::IS_INSTANCEOF)
            );

            foreach ($refMethod->getAttributes(RouteAttr::class, \ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                /** @var RouteAttr $routeAttr */
                $routeAttr = $attr->newInstance();

                //注册路由
                $nodeName = $routeAttr->name ?: $refMethod->getName();

                if (str_starts_with($nodeName, '/')) {
                    // 根路径
                    $rule = $this->route->rule($nodeName, "{$class}@{$refMethod->getName()}", $routeAttr->method);
                } else {
                    $rule = $routeGroup->addRule($nodeName, "{$class}@{$refMethod->getName()}", $routeAttr->method);
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
        return null;
    }
}
