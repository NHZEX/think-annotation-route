<?php

namespace Zxin\Think\Route\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Attribute;

/**
 * 路由中间件
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @NamedArgumentConstructor
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
