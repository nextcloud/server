<?php

declare(strict_types=1);

namespace libphonenumber;

/**
 * Number Format
 * @internal
 */
class NumberFormat
{
    protected string $pattern = '';
    protected bool $hasPattern = false;
    protected string $format = '';
    protected bool $hasFormat = false;
    /**
     * @var array<int,string>
     */
    protected array $leadingDigitsPattern = [];
    protected string $nationalPrefixFormattingRule = '';
    protected bool $hasNationalPrefixFormattingRule = false;
    protected bool $nationalPrefixOptionalWhenFormatting = false;
    protected bool $hasNationalPrefixOptionalWhenFormatting = false;
    protected string $domesticCarrierCodeFormattingRule = '';
    protected bool $hasDomesticCarrierCodeFormattingRule = false;

    public function hasPattern(): bool
    {
        return $this->hasPattern;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function setPattern(string $value): static
    {
        $this->hasPattern = true;
        $this->pattern = $value;

        return $this;
    }

    public function hasNationalPrefixOptionalWhenFormatting(): bool
    {
        return $this->hasNationalPrefixOptionalWhenFormatting;
    }

    public function getNationalPrefixOptionalWhenFormatting(): bool
    {
        return $this->nationalPrefixOptionalWhenFormatting;
    }

    public function setNationalPrefixOptionalWhenFormatting(bool $nationalPrefixOptionalWhenFormatting): static
    {
        $this->hasNationalPrefixOptionalWhenFormatting = true;
        $this->nationalPrefixOptionalWhenFormatting = $nationalPrefixOptionalWhenFormatting;

        return $this;
    }

    public function hasFormat(): bool
    {
        return $this->hasFormat;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $value): static
    {
        $this->hasFormat = true;
        $this->format = $value;

        return $this;
    }

    /**
     * @return array<int,string>
     */
    public function leadingDigitPatterns(): array
    {
        return $this->leadingDigitsPattern;
    }

    public function leadingDigitsPatternSize(): int
    {
        return count($this->leadingDigitsPattern);
    }

    public function getLeadingDigitsPattern(int $index): string
    {
        return $this->leadingDigitsPattern[$index];
    }

    /**
     * @param array<int,string> $patterns
     */
    public function setLeadingDigitsPattern(array $patterns): static
    {
        $this->leadingDigitsPattern = $patterns;
        return $this;
    }

    public function addLeadingDigitsPattern(string $value): static
    {
        $this->leadingDigitsPattern[] = $value;

        return $this;
    }

    public function hasNationalPrefixFormattingRule(): bool
    {
        return $this->hasNationalPrefixFormattingRule;
    }

    public function getNationalPrefixFormattingRule(): string
    {
        return $this->nationalPrefixFormattingRule;
    }

    public function setNationalPrefixFormattingRule(string $value): static
    {
        $this->hasNationalPrefixFormattingRule = true;
        $this->nationalPrefixFormattingRule = $value;

        return $this;
    }

    public function clearNationalPrefixFormattingRule(): static
    {
        $this->nationalPrefixFormattingRule = '';

        return $this;
    }

    public function hasDomesticCarrierCodeFormattingRule(): bool
    {
        return $this->hasDomesticCarrierCodeFormattingRule;
    }

    public function getDomesticCarrierCodeFormattingRule(): string
    {
        return $this->domesticCarrierCodeFormattingRule;
    }

    public function setDomesticCarrierCodeFormattingRule(string $value): static
    {
        $this->hasDomesticCarrierCodeFormattingRule = true;
        $this->domesticCarrierCodeFormattingRule = $value;

        return $this;
    }

    public function mergeFrom(NumberFormat $other): static
    {
        if ($other->hasPattern()) {
            $this->setPattern($other->getPattern());
        }
        if ($other->hasFormat()) {
            $this->setFormat($other->getFormat());
        }
        $leadingDigitsPatternSize = $other->leadingDigitsPatternSize();
        for ($i = 0; $i < $leadingDigitsPatternSize; $i++) {
            $this->addLeadingDigitsPattern($other->getLeadingDigitsPattern($i));
        }
        if ($other->hasNationalPrefixFormattingRule()) {
            $this->setNationalPrefixFormattingRule($other->getNationalPrefixFormattingRule());
        }
        if ($other->hasDomesticCarrierCodeFormattingRule()) {
            $this->setDomesticCarrierCodeFormattingRule($other->getDomesticCarrierCodeFormattingRule());
        }
        if ($other->hasNationalPrefixOptionalWhenFormatting()) {
            $this->setNationalPrefixOptionalWhenFormatting($other->getNationalPrefixOptionalWhenFormatting());
        }

        return $this;
    }
}
