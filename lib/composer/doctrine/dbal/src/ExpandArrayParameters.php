<?php

namespace Doctrine\DBAL;

use Doctrine\DBAL\ArrayParameters\Exception\MissingNamedParameter;
use Doctrine\DBAL\ArrayParameters\Exception\MissingPositionalParameter;
use Doctrine\DBAL\SQL\Parser\Visitor;
use Doctrine\DBAL\Types\Type;

use function array_fill;
use function array_key_exists;
use function count;
use function implode;
use function substr;

final class ExpandArrayParameters implements Visitor
{
    /** @var array<int,mixed>|array<string,mixed> */
    private $originalParameters;

    /** @var array<int,Type|int|string|null>|array<string,Type|int|string|null> */
    private $originalTypes;

    /** @var int */
    private $originalParameterIndex = 0;

    /** @var list<string> */
    private $convertedSQL = [];

    /** @var list<mixed> */
    private $convertedParameteres = [];

    /** @var array<int,Type|int|string|null> */
    private $convertedTypes = [];

    /**
     * @param array<int, mixed>|array<string, mixed>                             $parameters
     * @param array<int,Type|int|string|null>|array<string,Type|int|string|null> $types
     */
    public function __construct(array $parameters, array $types)
    {
        $this->originalParameters = $parameters;
        $this->originalTypes      = $types;
    }

    public function acceptPositionalParameter(string $sql): void
    {
        $index = $this->originalParameterIndex;

        if (! array_key_exists($index, $this->originalParameters)) {
            throw MissingPositionalParameter::new($index);
        }

        $this->acceptParameter($index, $this->originalParameters[$index]);

        $this->originalParameterIndex++;
    }

    public function acceptNamedParameter(string $sql): void
    {
        $name = substr($sql, 1);

        if (! array_key_exists($name, $this->originalParameters)) {
            throw MissingNamedParameter::new($name);
        }

        $this->acceptParameter($name, $this->originalParameters[$name]);
    }

    public function acceptOther(string $sql): void
    {
        $this->convertedSQL[] = $sql;
    }

    public function getSQL(): string
    {
        return implode('', $this->convertedSQL);
    }

    /**
     * @return list<mixed>
     */
    public function getParameters(): array
    {
        return $this->convertedParameteres;
    }

    /**
     * @param int|string $key
     * @param mixed      $value
     */
    private function acceptParameter($key, $value): void
    {
        if (! isset($this->originalTypes[$key])) {
            $this->convertedSQL[]         = '?';
            $this->convertedParameteres[] = $value;

            return;
        }

        $type = $this->originalTypes[$key];

        if ($type !== Connection::PARAM_INT_ARRAY && $type !== Connection::PARAM_STR_ARRAY) {
            $this->appendTypedParameter([$value], $type);

            return;
        }

        if (count($value) === 0) {
            $this->convertedSQL[] = 'NULL';

            return;
        }

        $this->appendTypedParameter($value, $type - Connection::ARRAY_PARAM_OFFSET);
    }

    /**
     * @return array<int,Type|int|string|null>
     */
    public function getTypes(): array
    {
        return $this->convertedTypes;
    }

    /**
     * @param list<mixed>          $values
     * @param Type|int|string|null $type
     */
    private function appendTypedParameter(array $values, $type): void
    {
        $this->convertedSQL[] = implode(', ', array_fill(0, count($values), '?'));

        $index = count($this->convertedParameteres);

        foreach ($values as $value) {
            $this->convertedParameteres[] = $value;
            $this->convertedTypes[$index] = $type;

            $index++;
        }
    }
}
