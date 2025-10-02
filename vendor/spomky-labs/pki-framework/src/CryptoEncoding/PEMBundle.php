<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoEncoding;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use RuntimeException;
use Stringable;
use UnexpectedValueException;
use function count;
use function is_string;
use const PREG_SET_ORDER;

/**
 * Container for multiple PEM objects.
 *
 * The order of PEMs shall be retained, eg. when read from a file.
 */
final class PEMBundle implements Countable, IteratorAggregate, Stringable
{
    /**
     * Array of PEM objects.
     *
     * @var PEM[]
     */
    private array $pems;

    private function __construct(PEM ...$pems)
    {
        $this->pems = $pems;
    }

    public function __toString(): string
    {
        return $this->string();
    }

    public static function create(PEM ...$pems): self
    {
        return new self(...$pems);
    }

    /**
     * Initialize from a string.
     */
    public static function fromString(string $str): self
    {
        $hasMatches = preg_match_all(PEM::PEM_REGEX, $str, $matches, PREG_SET_ORDER);
        if ($hasMatches === false || $hasMatches === 0) {
            throw new UnexpectedValueException('No PEM blocks.');
        }
        $pems = array_map(
            static function ($match) {
                $payload = preg_replace('/\s+/', '', $match[2]);
                if (! is_string($payload)) {
                    throw new UnexpectedValueException('Failed to decode PEM data.');
                }
                $data = base64_decode($payload, true);
                if ($data === false) {
                    throw new UnexpectedValueException('Failed to decode PEM data.');
                }
                return PEM::create($match[1], $data);
            },
            $matches
        );
        return self::create(...$pems);
    }

    /**
     * Initialize from a file.
     */
    public static function fromFile(string $filename): self
    {
        if (! is_readable($filename)) {
            throw new RuntimeException("Failed to read {$filename}.");
        }
        $str = file_get_contents($filename);
        if ($str === false) {
            throw new RuntimeException("Failed to read {$filename}.");
        }
        return self::fromString($str);
    }

    /**
     * Get self with PEM objects appended.
     */
    public function withPEMs(PEM ...$pems): self
    {
        $obj = clone $this;
        $obj->pems = array_merge($obj->pems, $pems);
        return $obj;
    }

    /**
     * Get all PEMs in a bundle.
     *
     * @return PEM[]
     */
    public function all(): array
    {
        return $this->pems;
    }

    /**
     * Get the first PEM in a bundle.
     */
    public function first(): PEM
    {
        if (count($this->pems) === 0) {
            throw new LogicException('No PEMs.');
        }
        return $this->pems[0];
    }

    /**
     * Get the last PEM in a bundle.
     */
    public function last(): PEM
    {
        if (count($this->pems) === 0) {
            throw new LogicException('No PEMs.');
        }
        return $this->pems[count($this->pems) - 1];
    }

    /**
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->pems);
    }

    /**
     * Get iterator for PEMs.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->pems);
    }

    /**
     * Encode bundle to a string of contiguous PEM blocks.
     */
    public function string(): string
    {
        return implode("\n", array_map(static fn (PEM $pem) => $pem->string(), $this->pems));
    }
}
