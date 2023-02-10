<?php

declare(strict_types=1);

namespace Zxin\Think\Route\Annotation;

use Attribute;

/**
 * 注册资源路由
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
