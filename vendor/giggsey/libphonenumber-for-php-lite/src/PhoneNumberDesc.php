<?php

declare(strict_types=1);

namespace libphonenumber;

/**
 * Phone Number Description
 * @internal
 */
class PhoneNumberDesc
{
    protected bool $hasNationalNumberPattern = false;
    protected string $nationalNumberPattern = '';
    protected bool $hasExampleNumber = false;
    protected string $exampleNumber = '';
    /**
     * @var int[]
     */
    protected array $possibleLength = [];
    /**
     * @var int[]
     */
    protected array $possibleLengthLocalOnly = [];

    /**
     * @return int[]
     */
    public function getPossibleLength(): array
    {
        return $this->possibleLength;
    }

    /**
     * @param int[] $possibleLength
     */
    public function setPossibleLength(array $possibleLength): static
    {
        $this->possibleLength = $possibleLength;
        return $this;
    }

    public function addPossibleLength(int $possibleLength): void
    {
        if (!in_array($possibleLength, $this->possibleLength, true)) {
            $this->possibleLength[] = $possibleLength;
        }
    }

    public function clearPossibleLength(): void
    {
        $this->possibleLength = [];
    }

    /**
     * @return int[]
     */
    public function getPossibleLengthLocalOnly(): array
    {
        return $this->possibleLengthLocalOnly;
    }

    /**
     * @param int[] $possibleLengthLocalOnly
     */
    public function setPossibleLengthLocalOnly(array $possibleLengthLocalOnly): static
    {
        $this->possibleLengthLocalOnly = $possibleLengthLocalOnly;

        return $this;
    }

    public function addPossibleLengthLocalOnly(int $possibleLengthLocalOnly): void
    {
        if (!in_array($possibleLengthLocalOnly, $this->possibleLengthLocalOnly, true)) {
            $this->possibleLengthLocalOnly[] = $possibleLengthLocalOnly;
        }
    }

    public function clearPossibleLengthLocalOnly(): void
    {
        $this->possibleLengthLocalOnly = [];
    }

    /**
     * @return boolean
     */
    public function hasNationalNumberPattern(): bool
    {
        return $this->hasNationalNumberPattern;
    }

    public function getNationalNumberPattern(): string
    {
        return $this->nationalNumberPattern;
    }

    public function setNationalNumberPattern(string $value): static
    {
        $this->hasNationalNumberPattern = true;
        $this->nationalNumberPattern = $value;

        return $this;
    }

    public function hasExampleNumber(): bool
    {
        return $this->hasExampleNumber;
    }

    public function getExampleNumber(): string
    {
        return $this->exampleNumber;
    }

    public function setExampleNumber(string $value): static
    {
        $this->hasExampleNumber = true;
        $this->exampleNumber = $value;

        return $this;
    }

    private static self $emptyObject;

    /**
     * Used for metadata as a shortcut to an empty object
     * Use the same object to reduce load further
     * @internal
     */
    public static function empty(): self
    {
        if (!isset(self::$emptyObject)) {
            self::$emptyObject = (new self())
                ->setPossibleLength([-1]);
        }

        return self::$emptyObject;
    }
}
