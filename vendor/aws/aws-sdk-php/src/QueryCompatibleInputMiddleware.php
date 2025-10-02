<?php
namespace Aws;

use Aws\Api\ListShape;
use Aws\Api\MapShape;
use Aws\Api\Service;
use Aws\Api\Shape;
use Aws\Api\StructureShape;
use Closure;

/**
 * Inspects command input values and casts them to their modeled type.
 * This covers query compatible services which have migrated from query
 * to JSON wire protocols.
 *
 * @internal
 */
class QueryCompatibleInputMiddleware
{
    /** @var callable */
    private $nextHandler;

    /** @var Service */
    private $service;

    /** @var CommandInterface */
    private $command;

    /**
     * Create a middleware wrapper function.
     *
     * @param Service $service
     * @return Closure
     */
    public static function wrap(Service $service) : Closure
    {
        return static function (callable $handler) use ($service) {
            return new self($handler, $service);
        };
    }

    public function __construct(callable $nextHandler, Service $service)
    {
        $this->service = $service;
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(CommandInterface $cmd)
    {
        $this->command = $cmd;
        $nextHandler = $this->nextHandler;
        $op = $this->service->getOperation($cmd->getName());
        $inputMembers = $op->getInput()->getMembers();
        $input = $cmd->toArray();

        foreach ($input as $param => $value) {
            if (isset($inputMembers[$param])) {
                $shape = $inputMembers[$param];
                $this->processInput($value, $shape, [$param]);
            }
        }

        return $nextHandler($this->command);
    }

    /**
     * Recurses a given input shape. if a given scalar input does not match its
     * modeled type, it is cast to its modeled type.
     *
     * @param $input
     * @param $shape
     * @param array $path
     *
     * @return void
     */
    private function processInput($input, $shape, array $path) : void
    {
        switch ($shape->getType()) {
            case 'structure':
                $this->processStructure($input, $shape, $path);
                break;
            case 'list':
                $this->processList($input, $shape, $path);
                break;
            case 'map':
                $this->processMap($input, $shape, $path);
                break;
            default:
                $this->processScalar($input, $shape, $path);
        }
    }

    /**
     * @param array $input
     * @param StructureShape $shape
     * @param array $path
     *
     * @return void
     */
    private function processStructure(
        array $input,
        StructureShape $shape,
        array $path
    ) : void
    {
        foreach ($input as $param => $value) {
            if ($shape->hasMember($param)) {
                $memberPath = array_merge($path, [$param]);
                $this->processInput($value, $shape->getMember($param), $memberPath);
            }
        }
    }

    /**
     * @param array $input
     * @param ListShape $shape
     * @param array $path
     *
     * @return void
     */
    private function processList(
        array $input,
        ListShape $shape,
        array $path
    ) : void
    {
        foreach ($input as $param => $value) {
            $memberPath = array_merge($path, [$param]);
            $this->processInput($value, $shape->getMember(), $memberPath);
        }
    }

    /**
     * @param array $input
     * @param MapShape $shape
     * @param array $path
     *
     * @return void
     */
    private function processMap(array $input, MapShape $shape, array $path) : void
    {
        foreach ($input as $param => $value) {
            $memberPath = array_merge($path, [$param]);
            $this->processInput($value, $shape->getValue(), $memberPath);
        }
    }

    /**
     * @param $input
     * @param Shape $shape
     * @param array $path
     *
     * @return void
     */
    private function processScalar($input, Shape $shape, array $path) : void
    {
        $expectedType = $shape->getType();

        if (!$this->isModeledType($input, $expectedType)) {
            trigger_error(
                "The provided type for `". implode(' -> ', $path) ."` value was `"
                . (gettype($input) ===  'double' ? 'float' : gettype($input)) . "`."
                . " The modeled type is `{$expectedType}`.",
                E_USER_WARNING
            );
            $value = $this->castValue($input, $expectedType);
            $this->changeValueAtPath($path, $value);
        }
    }

    /**
     * Modifies command in place
     *
     * @param array $path
     * @param $newValue
     *
     * @return void
     */
    private function changeValueAtPath(array $path, $newValue) : void
    {
        $commandRef = &$this->command;

        foreach ($path as $segment) {
            if (!isset($commandRef[$segment])) {
                return;
            }
            $commandRef = &$commandRef[$segment];
        }
        $commandRef = $newValue;
    }

    /**
     * @param $value
     * @param $type
     *
     * @return bool
     */
    private function isModeledType($value, $type) : bool
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'integer':
            case 'long':
                return is_int($value);
            case 'float':
                return is_float($value);
            default:
                return true;
        }
    }

    /**
     * @param $value
     * @param $type
     *
     * @return float|int|mixed|string
     */
    private function castValue($value, $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'long' :
                return $value + 0;
            case 'float':
                return (float) $value;
            case 'string':
                return (string) $value;
            default:
                return $value;
        }
    }
}
