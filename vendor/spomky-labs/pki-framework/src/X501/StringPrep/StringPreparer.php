<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\StringPrep;

/**
 * Implement Internationalized String Preparation as specified by RFC 4518.
 *
 * @see https://tools.ietf.org/html/rfc4518
 */
final class StringPreparer
{
    final public const STEP_TRANSCODE = 1;

    final public const STEP_MAP = 2;

    final public const STEP_NORMALIZE = 3;

    final public const STEP_PROHIBIT = 4;

    final public const STEP_CHECK_BIDI = 5;

    final public const STEP_INSIGNIFICANT_CHARS = 6;

    /**
     * @param PrepareStep[] $_steps Preparation steps to apply
     */
    private function __construct(
        /**
         * Preparation steps.
         */
        protected array $_steps
    ) {
    }

    /**
     * Get default instance for given string type.
     *
     * @param int $string_type ASN.1 string type tag.
     */
    public static function forStringType(int $string_type): self
    {
        $steps = [
            self::STEP_TRANSCODE => TranscodeStep::create($string_type),
            self::STEP_MAP => MapStep::create(),
            self::STEP_NORMALIZE => new NormalizeStep(),
            self::STEP_PROHIBIT => new ProhibitStep(),
            self::STEP_CHECK_BIDI => new CheckBidiStep(),
            // @todo Vary by string type
            self::STEP_INSIGNIFICANT_CHARS => new InsignificantNonSubstringSpaceStep(),
        ];
        return new self($steps);
    }

    /**
     * Get self with case folding set.
     *
     * @param bool $fold True to apply case folding
     */
    public function withCaseFolding(bool $fold): self
    {
        $obj = clone $this;
        $obj->_steps[self::STEP_MAP] = MapStep::create($fold);
        return $obj;
    }

    /**
     * Prepare string.
     */
    public function prepare(string $string): string
    {
        foreach ($this->_steps as $step) {
            $string = $step->apply($string);
        }
        return $string;
    }
}
