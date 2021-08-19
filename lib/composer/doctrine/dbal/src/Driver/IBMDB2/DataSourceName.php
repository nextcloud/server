<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\IBMDB2;

use function implode;
use function sprintf;
use function strpos;

/**
 * IBM DB2 DSN
 */
final class DataSourceName
{
    /** @var string */
    private $string;

    private function __construct(string $string)
    {
        $this->string = $string;
    }

    public function toString(): string
    {
        return $this->string;
    }

    /**
     * Creates the object from an array representation
     *
     * @param array<string,mixed> $params
     */
    public static function fromArray(array $params): self
    {
        $chunks = [];

        foreach ($params as $key => $value) {
            $chunks[] = sprintf('%s=%s', $key, $value);
        }

        return new self(implode(';', $chunks));
    }

    /**
     * Creates the object from the given DBAL connection parameters.
     *
     * @param array<string,mixed> $params
     */
    public static function fromConnectionParameters(array $params): self
    {
        if (isset($params['dbname']) && strpos($params['dbname'], '=') !== false) {
            return new self($params['dbname']);
        }

        $dsnParams = [];

        foreach (
            [
                'host'     => 'HOSTNAME',
                'port'     => 'PORT',
                'protocol' => 'PROTOCOL',
                'dbname'   => 'DATABASE',
                'user'     => 'UID',
                'password' => 'PWD',
            ] as $dbalParam => $dsnParam
        ) {
            if (! isset($params[$dbalParam])) {
                continue;
            }

            $dsnParams[$dsnParam] = $params[$dbalParam];
        }

        return self::fromArray($dsnParams);
    }
}
