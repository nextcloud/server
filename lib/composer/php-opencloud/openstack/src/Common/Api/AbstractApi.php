<?php

declare(strict_types=1);

namespace OpenStack\Common\Api;

abstract class AbstractApi implements ApiInterface
{
    protected $params;

    protected function isRequired(array $param): array
    {
        return array_merge($param, ['required' => true]);
    }

    protected function notRequired(array $param): array
    {
        return array_merge($param, ['required' => false]);
    }

    protected function query(array $param): array
    {
        return array_merge($param, ['location' => AbstractParams::QUERY]);
    }

    protected function url(array $param): array
    {
        return array_merge($param, ['location' => AbstractParams::URL]);
    }

    public function documented(array $param): array
    {
        return array_merge($param, ['required' => true]);
    }
}
