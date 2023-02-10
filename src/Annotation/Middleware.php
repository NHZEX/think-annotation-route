<?php

declare(strict_types=1);

namespace Zxin\Think\Route\Annotation;

use Attribute;

/**
 * 路由中间件
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Middleware extends Base
{
    public function __construct(
        public string $name,
        public array $params = [],
    ) {
    }
}
