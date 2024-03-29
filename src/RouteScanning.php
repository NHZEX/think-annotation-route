<?php

namespace Zxin\Think\Route;

use ReflectionClass;
use ReflectionException;
use think\App;
use Zxin\Think\Annotation\Scanning;
use Zxin\Think\Route\Annotation\Group as GroupAttr;
use Zxin\Think\Route\Annotation\Resource as ResourceAttr;
use Zxin\Think\Route\Annotation\ResourceRule as ResourceRuleAttr;
use Zxin\Think\Route\Annotation\Route as RouteAttr;
use Zxin\Think\Route\Annotation\Middleware as MiddlewareAttr;
use ReflectionMethod;
use ReflectionAttribute;

class RouteScanning
{
    private App    $app;
    private string $controllerLayer;

    public function __construct(App $app)
    {
        $this->app = $app;

        $this->controllerLayer = $this->app->route->config('controller_layer') ?: 'controller';
    }

    public function classToRouteName(string $class): string
    {
        $controllerName = \str_replace("app\\{$this->controllerLayer}\\", '', $class);
        return \str_replace('\\', '.', $controllerName);
    }

    public function scan(): array
    {
        $cutPathLen = \strlen($this->app->getRootPath());
        $scanning = new Scanning($this->app);

        $items = [];

        $refMap = [];

        foreach ($scanning->scanningClass() as $file => $class) {
            try {
                $refClass = new ReflectionClass($class);
            } catch (ReflectionException $ex) {
                continue;
            }
            if ($refClass->isAbstract() || $refClass->isTrait()) {
                continue;
            }

            $attr = $refClass->getAttributes(GroupAttr::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
            /** @var GroupAttr|null $groupAttr */
            $groupAttr = $attr ? $attr->newInstance() : null;

            $sort = $groupAttr ? $groupAttr->registerSort : 1000;

            $refMap[$class] = $refClass;

            /** @var MiddlewareAttr[] $middlewareAttr */
            $middlewareAttr = \array_map(
                fn ($attr) => $attr->newInstance(),
                $refClass->getAttributes(MiddlewareAttr::class, ReflectionAttribute::IS_INSTANCEOF)
            );

            $attr = $refClass->getAttributes(ResourceAttr::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
            /** @var ResourceAttr|null $groupAttr */
            $resourceAttr = $attr ? $attr->newInstance() : null;

            $filename = (string) $file;
            $filename = \substr($filename, $cutPathLen);
            $items[] = [
                'file'          => $filename,
                'class'         => $class,
                'controller'    => $this->classToRouteName($class),
                'sort'          => $sort,
                'group'         => $groupAttr,
                'middleware'    => $middlewareAttr,
                'resource'      => $resourceAttr,
                'resourceItems' => [],
                'routeItems'    => [],
            ];
        }

        \usort($items, fn ($a, $b) => $b['sort'] <=> $a['sort']);


        foreach ($items as &$item) {
            $classRef = $refMap[$item['class']];
            $this->parseMethod($item, $classRef);
        }

        return $items;
    }

    /**
     * @param array           $groupItem
     * @param ReflectionClass $refClass
     */
    public function parseMethod(array &$groupItem, ReflectionClass $refClass): void
    {
        // todo 支持排序

        // 资源路由
        foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $refMethod) {
            $methodName = $refMethod->getName();

            if (!$refMethod->isPublic() || $refMethod->isStatic()) {
                continue;
            }
            if (\str_starts_with($methodName, '_')) {
                continue;
            }

            $attr = $refMethod->getAttributes(ResourceRuleAttr::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
            /** @var ResourceRuleAttr|null $rrule */
            $rrule = $attr ? $attr->newInstance() : null;

            if ($rrule) {
                $groupItem['resourceItems'][] = [
                    'method' => $methodName,
                    'attr'   => $rrule,
                ];
            }

            /** @var RouteAttr[] $route */
            $route = \array_map(
                fn ($attr) => $attr->newInstance(),
                $refMethod->getAttributes(RouteAttr::class, ReflectionAttribute::IS_INSTANCEOF),
            );

            if ($route) {
                /** @var MiddlewareAttr[] $middleware */
                $middleware = \array_map(
                    fn ($attr) => $attr->newInstance(),
                    $refMethod->getAttributes(MiddlewareAttr::class, ReflectionAttribute::IS_INSTANCEOF),
                );

                \usort($route, fn ($a, $b) => $b->registerSort <=> $a->registerSort);

                $groupItem['routeItems'][] = [
                    'method'     => $refMethod->getName(),
                    'route'      => $route,
                    'middleware' => $middleware,
                ];
            }
        }

        $routeItems = &$groupItem['routeItems'];

        \usort($routeItems, fn ($a, $b) => $b['route'][0]->registerSort <=> $a['route'][0]->registerSort);
    }
}
