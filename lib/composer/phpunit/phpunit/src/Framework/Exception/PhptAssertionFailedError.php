<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class PhptAssertionFailedError extends AssertionFailedError
{
    private readonly string $syntheticFile;
    private readonly int $syntheticLine;
    private readonly array $syntheticTrace;
    private readonly string $diff;

    public function __construct(string $message, int $code, string $file, int $line, array $trace, string $diff)
    {
        parent::__construct($message, $code);

        $this->syntheticFile  = $file;
        $this->syntheticLine  = $line;
        $this->syntheticTrace = $trace;
        $this->diff           = $diff;
    }

    public function syntheticFile(): string
    {
        return $this->syntheticFile;
    }

    public function syntheticLine(): int
    {
        return $this->syntheticLine;
    }

    public function syntheticTrace(): array
    {
        return $this->syntheticTrace;
    }

    public function diff(): string
    {
        return $this->diff;
    }
}
