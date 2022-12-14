<?php

namespace Zxin\Think\Route\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Attribute;

/**
 * 注册资源路由
 * @package Zxin\Think\Auth\Annotation
 * @Annotation
 * @Target({"METHOD"})
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ResourceRule extends Base
{
    public function __construct(
        public ?string            $name = null,
        /**
         * 请求类型
         * @Enum({"GET","POST","PUT","DELETE","PATCH","OPTIONS","HEAD"})
         */
        public string            $method = 'GET',
    ) {
    }
}
