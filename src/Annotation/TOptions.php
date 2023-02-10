<?php

declare(strict_types=1);

namespace Zxin\Think\Route\Annotation;

trait TOptions
{
    public function getOptions(): array
    {
        $result = [];
        foreach (
            [
                'middleware',
                'ext',
                'deny_ext',
                'https',
                'domain',
                'complete_match',
                'cache',
                'ajax',
                'pjax',
                'json',
                'filter',
                'append',
            ] as $name
        ) {
            if (!isset($this->$name)) {
                continue;
            }
            $result[] = $this->$name;
        }
        return $result;
    }
}
