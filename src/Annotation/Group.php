<?php

declare(strict_types=1);

namespace Zxin\Think\Route\Annotation;

use Attribute;

/**
 * 路由分组
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Group extends Base
{
    use TOptions;

    public function __construct(
        public ?string           $name = null,
        public null|string|array $middleware = null,
        // ==== 通用参数 ====
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
        public int               $registerSort = 1000,
    ) {
    }
}
