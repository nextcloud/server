<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\AbstractOracleDriver;

use function implode;
use function is_array;
use function sprintf;

/**
 * Represents an Oracle Easy Connect string
 *
 * @link https://docs.oracle.com/database/121/NETAG/naming.htm
 */
final class EasyConnectString
{
    private string $string;

    private function __construct(string $string)
    {
        $this->string = $string;
    }

    public function __toString(): string
    {
        return $this->string;
    }

    /**
     * Creates the object from an array representation
     *
     * @param mixed[] $params
     */
    public static function fromArray(array $params): self
    {
        return new self(self::renderParams($params));
    }

    /**
     * Creates the object from the given DBAL connection parameters.
     *
     * @param mixed[] $params
     */
    public static function fromConnectionParameters(array $params): self
    {
        if (isset($params['connectstring'])) {
            return new self($params['connectstring']);
        }

        if (! isset($params['host'])) {
            return new self($params['dbname'] ?? '');
        }

        $connectData = [];

        if (isset($params['servicename']) || isset($params['dbname'])) {
            $serviceKey = 'SID';

            if (isset($params['service'])) {
                $serviceKey = 'SERVICE_NAME';
            }

            $serviceName = $params['servicename'] ?? $params['dbname'];

            $connectData[$serviceKey] = $serviceName;
        }

        if (isset($params['instancename'])) {
            $connectData['INSTANCE_NAME'] = $params['instancename'];
        }

        if (! empty($params['pooled'])) {
            $connectData['SERVER'] = 'POOLED';
        }

        return self::fromArray([
            'DESCRIPTION' => [
                'ADDRESS' => [
                    'PROTOCOL' => 'TCP',
                    'HOST' => $params['host'],
                    'PORT' => $params['port'] ?? 1521,
                ],
                'CONNECT_DATA' => $connectData,
            ],
        ]);
    }

    /** @param mixed[] $params */
    private static function renderParams(array $params): string
    {
        $chunks = [];

        foreach ($params as $key => $value) {
            $string = self::renderValue($value);

            if ($string === '') {
                continue;
            }

            $chunks[] = sprintf('(%s=%s)', $key, $string);
        }

        return implode('', $chunks);
    }

    /** @param mixed $value */
    private static function renderValue($value): string
    {
        if (is_array($value)) {
            return self::renderParams($value);
        }

        return (string) $value;
    }
}
