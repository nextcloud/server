<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TestRunner\TestResult\Issues;

use PHPUnit\Event\Code\Test;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class Issue
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $file;

    /**
     * @psalm-var positive-int
     */
    private readonly int $line;

    /**
     * @psalm-var non-empty-string
     */
    private readonly string $description;

    /**
     * @psalm-var non-empty-array<non-empty-string, array{test: Test, count: int}>
     */
    private array $triggeringTests;

    /**
     * @psalm-param non-empty-string $file
     * @psalm-param positive-int $line
     * @psalm-param non-empty-string $description
     */
    public static function from(string $file, int $line, string $description, Test $triggeringTest): self
    {
        return new self($file, $line, $description, $triggeringTest);
    }

    /**
     * @psalm-param non-empty-string $file
     * @psalm-param positive-int $line
     * @psalm-param non-empty-string $description
     */
    private function __construct(string $file, int $line, string $description, Test $triggeringTest)
    {
        $this->file        = $file;
        $this->line        = $line;
        $this->description = $description;

        $this->triggeringTests = [
            $triggeringTest->id() => [
                'test'  => $triggeringTest,
                'count' => 1,
            ],
        ];
    }

    public function triggeredBy(Test $test): void
    {
        if (isset($this->triggeringTests[$test->id()])) {
            $this->triggeringTests[$test->id()]['count']++;

            return;
        }

        $this->triggeringTests[$test->id()] = [
            'test'  => $test,
            'count' => 1,
        ];
    }

    /**
     * @psalm-return non-empty-string
     */
    public function file(): string
    {
        return $this->file;
    }

    /**
     * @psalm-return positive-int
     */
    public function line(): int
    {
        return $this->line;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * @psalm-return non-empty-array<non-empty-string, array{test: Test, count: int}>
     */
    public function triggeringTests(): array
    {
        return $this->triggeringTests;
    }
}
