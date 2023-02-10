<?php

declare(strict_types=1);

namespace Zxin\Think\Route\Annotation;

use Attribute;

/**
 * 注册资源路由
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Resource extends Base
{
    use TOptions;

    public function __construct(
        public string            $name,
        // 定义资源变量名
        public ?array            $vars = null,
        // 仅允许特定操作
        public ?array            $only = null,
        // 排除特定操作
        public ?array            $except = null,
        // ==== 通用参数 ====
        public ?string           $ext = null,
        public ?string           $deny_ext = null,
        public ?bool             $https = null,
        public ?string           $domain = null,
        public ?bool             $completeMatch = null,
        public null|string|array $cache = null,
        public ?bool             $ajax = null,
        public ?bool             $pjax = null,
        public ?bool             $json = null,
        public ?array            $filter = null,
        public ?array            $append = null,
        public ?array            $pattern = null,
    ) {
    }
}
