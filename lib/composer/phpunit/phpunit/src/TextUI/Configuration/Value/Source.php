<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI\Configuration;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @psalm-immutable
 */
final class Source
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly ?string $baseline;
    private readonly bool $ignoreBaseline;
    private readonly FilterDirectoryCollection $includeDirectories;
    private readonly FileCollection $includeFiles;
    private readonly FilterDirectoryCollection $excludeDirectories;
    private readonly FileCollection $excludeFiles;
    private readonly bool $restrictDeprecations;
    private readonly bool $restrictNotices;
    private readonly bool $restrictWarnings;
    private readonly bool $ignoreSuppressionOfDeprecations;
    private readonly bool $ignoreSuppressionOfPhpDeprecations;
    private readonly bool $ignoreSuppressionOfErrors;
    private readonly bool $ignoreSuppressionOfNotices;
    private readonly bool $ignoreSuppressionOfPhpNotices;
    private readonly bool $ignoreSuppressionOfWarnings;
    private readonly bool $ignoreSuppressionOfPhpWarnings;

    /**
     * @psalm-param non-empty-string $baseline
     */
    public function __construct(?string $baseline, bool $ignoreBaseline, FilterDirectoryCollection $includeDirectories, FileCollection $includeFiles, FilterDirectoryCollection $excludeDirectories, FileCollection $excludeFiles, bool $restrictDeprecations, bool $restrictNotices, bool $restrictWarnings, bool $ignoreSuppressionOfDeprecations, bool $ignoreSuppressionOfPhpDeprecations, bool $ignoreSuppressionOfErrors, bool $ignoreSuppressionOfNotices, bool $ignoreSuppressionOfPhpNotices, bool $ignoreSuppressionOfWarnings, bool $ignoreSuppressionOfPhpWarnings)
    {
        $this->baseline                           = $baseline;
        $this->ignoreBaseline                     = $ignoreBaseline;
        $this->includeDirectories                 = $includeDirectories;
        $this->includeFiles                       = $includeFiles;
        $this->excludeDirectories                 = $excludeDirectories;
        $this->excludeFiles                       = $excludeFiles;
        $this->restrictDeprecations               = $restrictDeprecations;
        $this->restrictNotices                    = $restrictNotices;
        $this->restrictWarnings                   = $restrictWarnings;
        $this->ignoreSuppressionOfDeprecations    = $ignoreSuppressionOfDeprecations;
        $this->ignoreSuppressionOfPhpDeprecations = $ignoreSuppressionOfPhpDeprecations;
        $this->ignoreSuppressionOfErrors          = $ignoreSuppressionOfErrors;
        $this->ignoreSuppressionOfNotices         = $ignoreSuppressionOfNotices;
        $this->ignoreSuppressionOfPhpNotices      = $ignoreSuppressionOfPhpNotices;
        $this->ignoreSuppressionOfWarnings        = $ignoreSuppressionOfWarnings;
        $this->ignoreSuppressionOfPhpWarnings     = $ignoreSuppressionOfPhpWarnings;
    }

    /**
     * @psalm-assert-if-true !null $this->baseline
     */
    public function useBaseline(): bool
    {
        return $this->hasBaseline() && !$this->ignoreBaseline;
    }

    /**
     * @psalm-assert-if-true !null $this->baseline
     */
    public function hasBaseline(): bool
    {
        return $this->baseline !== null;
    }

    /**
     * @throws NoBaselineException
     *
     * @psalm-return non-empty-string
     */
    public function baseline(): string
    {
        if (!$this->hasBaseline()) {
            throw new NoBaselineException;
        }

        return $this->baseline;
    }

    public function includeDirectories(): FilterDirectoryCollection
    {
        return $this->includeDirectories;
    }

    public function includeFiles(): FileCollection
    {
        return $this->includeFiles;
    }

    public function excludeDirectories(): FilterDirectoryCollection
    {
        return $this->excludeDirectories;
    }

    public function excludeFiles(): FileCollection
    {
        return $this->excludeFiles;
    }

    public function notEmpty(): bool
    {
        return $this->includeDirectories->notEmpty() || $this->includeFiles->notEmpty();
    }

    public function restrictDeprecations(): bool
    {
        return $this->restrictDeprecations;
    }

    public function restrictNotices(): bool
    {
        return $this->restrictNotices;
    }

    public function restrictWarnings(): bool
    {
        return $this->restrictWarnings;
    }

    public function ignoreSuppressionOfDeprecations(): bool
    {
        return $this->ignoreSuppressionOfDeprecations;
    }

    public function ignoreSuppressionOfPhpDeprecations(): bool
    {
        return $this->ignoreSuppressionOfPhpDeprecations;
    }

    public function ignoreSuppressionOfErrors(): bool
    {
        return $this->ignoreSuppressionOfErrors;
    }

    public function ignoreSuppressionOfNotices(): bool
    {
        return $this->ignoreSuppressionOfNotices;
    }

    public function ignoreSuppressionOfPhpNotices(): bool
    {
        return $this->ignoreSuppressionOfPhpNotices;
    }

    public function ignoreSuppressionOfWarnings(): bool
    {
        return $this->ignoreSuppressionOfWarnings;
    }

    public function ignoreSuppressionOfPhpWarnings(): bool
    {
        return $this->ignoreSuppressionOfPhpWarnings;
    }
}
