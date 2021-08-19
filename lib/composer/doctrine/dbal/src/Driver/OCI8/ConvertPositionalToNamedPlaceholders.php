<?php

namespace Doctrine\DBAL\Driver\OCI8;

use Doctrine\DBAL\SQL\Parser\Visitor;

use function count;
use function implode;

/**
 * Converts positional (?) into named placeholders (:param<num>).
 *
 * Oracle does not support positional parameters, hence this method converts all
 * positional parameters into artificially named parameters.
 *
 * @internal This class is not covered by the backward compatibility promise
 */
final class ConvertPositionalToNamedPlaceholders implements Visitor
{
    /** @var list<string> */
    private $buffer = [];

    /** @var array<int,string> */
    private $parameterMap = [];

    public function acceptOther(string $sql): void
    {
        $this->buffer[] = $sql;
    }

    public function acceptPositionalParameter(string $sql): void
    {
        $position = count($this->parameterMap) + 1;
        $param    = ':param' . $position;

        $this->parameterMap[$position] = $param;

        $this->buffer[] = $param;
    }

    public function acceptNamedParameter(string $sql): void
    {
        $this->buffer[] = $sql;
    }

    public function getSQL(): string
    {
        return implode('', $this->buffer);
    }

    /**
     * @return array<int,string>
     */
    public function getParameterMap(): array
    {
        return $this->parameterMap;
    }
}
