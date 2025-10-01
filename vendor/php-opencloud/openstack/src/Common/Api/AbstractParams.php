<?php

declare(strict_types=1);

namespace OpenStack\Common\Api;

abstract class AbstractParams
{
    // locations
    public const QUERY  = 'query';
    public const HEADER = 'header';
    public const URL    = 'url';
    public const JSON   = 'json';
    public const RAW    = 'raw';

    // types
    public const STRING_TYPE  = 'string';
    public const BOOL_TYPE    = 'boolean';
    public const BOOLEAN_TYPE = self::BOOL_TYPE;
    public const OBJECT_TYPE  = 'object';
    public const ARRAY_TYPE   = 'array';
    public const NULL_TYPE    = 'NULL';
    public const INT_TYPE     = 'integer';
    public const INTEGER_TYPE = self::INT_TYPE;

    public static function isSupportedLocation(string $val): bool
    {
        return in_array($val, [self::QUERY, self::HEADER, self::URL, self::JSON, self::RAW]);
    }

    public function limit(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => 'query',
            'description' => <<<DESC
This will limit the total amount of elements returned in a list up to the number specified. For example, specifying a
limit of 10 will return 10 elements, regardless of the actual count.
DESC
        ];
    }

    public function marker(): array
    {
        return [
            'type'        => 'string',
            'location'    => 'query',
            'description' => <<<DESC
Specifying a marker will begin the list from the value specified. Elements will have a particular attribute that
identifies them, such as a name or ID. The marker value will search for an element whose identifying attribute matches
the marker value, and begin the list from there.
DESC
        ];
    }

    public function id(string $type): array
    {
        return [
            'description' => sprintf('The unique ID, or identifier, for the %s', $type),
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
        ];
    }

    public function idPath(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::URL,
            'description' => 'The unique ID of the resource',
        ];
    }

    public function name(string $resource): array
    {
        return [
            'description' => sprintf('The name of the %s', $resource),
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
        ];
    }

    public function sortDir(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'sort_dir',
            'description' => 'Sorts by one or more sets of attribute and sort direction combinations.',
            'enum'        => ['asc', 'desc'],
        ];
    }

    public function sortKey(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'sort_key',
            'description' => 'Sorts by one or more sets of attribute and sort direction combinations.',
        ];
    }

    public function allTenants(): array
    {
        return [
            'type'        => self::BOOL_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'all_tenants',
            'description' => '(Admin only) Set this to true to pull volume information from all tenants.',
        ];
    }
}
