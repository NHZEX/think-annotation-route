<?php

declare(strict_types=1);

namespace Zxin\Think\Route\Annotation;

use Attribute;

/**
 * 注册路由
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Route extends Base
{
    use TOptions;

    public function __construct(
        public ?string           $name = null,
        /**
         * 请求类型
         * "GET","POST","PUT","DELETE","PATCH","OPTIONS","HEAD"
         */
        public string            $method = "*",
        public null|string|array $middleware = null,
        // 后缀
        public ?string           $ext = null,
        public ?string           $deny_ext = null,
        public ?bool             $https = null,
        public ?string           $domain = null,
        public ?bool             $complete_match = null,
        public null|string|array $cache = null,
        public ?bool             $ajax = null,
        public ?bool             $pjax = null,
        public ?bool             $json = null,
        public ?array            $filter = null,
        public ?array            $append = null,
        public ?array            $pattern = null,
        // ==== 特殊参数 ====
        // 单独设置路由到特定组
        public ?string           $setGroup = null,
        // 设置路由注册顺序
        public int               $registerSort = 1000,
    ) {
    }
}
