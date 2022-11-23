<?php

namespace Zxin\Think\Route\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * 路由分组
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Group extends Rule
{
}
