<?php

namespace libphonenumber;

/**
 * Number Format
 */
class NumberFormat
{
    /**
     * @var string
     */
    protected $pattern;
    /**
     * @var bool
     */
    protected $hasPattern = false;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var bool
     */
    protected $hasFormat = false;

    /**
     * @var array
     */
    protected $leadingDigitsPattern = array();

    /**
     * @var string
     */
    protected $nationalPrefixFormattingRule;

    /**
     * @var bool
     */
    protected $hasNationalPrefixFormattingRule = false;
    /**
     * @var bool
     */
    protected $nationalPrefixOptionalWhenFormatting = false;

    /**
     * @var bool
     */
    protected $hasNationalPrefixOptionalWhenFormatting = false;

    /**
     * @var string
     */
    protected $domesticCarrierCodeFormattingRule;

    /**
     * @var bool
     */
    protected $hasDomesticCarrierCodeFormattingRule = false;

    public function __construct()
    {
        $this->clear();
    }

    /**
     * @return NumberFormat
     */
    public function clear()
    {
        $this->hasPattern = false;
        $this->pattern = null;

        $this->hasFormat = false;
        $this->format = null;

        $this->leadingDigitsPattern = array();

        $this->hasNationalPrefixFormattingRule = false;
        $this->nationalPrefixFormattingRule = null;

        $this->hasNationalPrefixOptionalWhenFormatting = false;
        $this->nationalPrefixOptionalWhenFormatting = false;

        $this->hasDomesticCarrierCodeFormattingRule = false;
        $this->domesticCarrierCodeFormattingRule = null;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasPattern()
    {
        return $this->hasPattern;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param string $value
     * @return NumberFormat
     */
    public function setPattern($value)
    {
        $this->hasPattern = true;
        $this->pattern = $value;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasNationalPrefixOptionalWhenFormatting()
    {
        return $this->hasNationalPrefixOptionalWhenFormatting;
    }

    /**
     * @return boolean
     */
    public function getNationalPrefixOptionalWhenFormatting()
    {
        return $this->nationalPrefixOptionalWhenFormatting;
    }

    /**
     * @param boolean $nationalPrefixOptionalWhenFormatting
     */
    public function setNationalPrefixOptionalWhenFormatting($nationalPrefixOptionalWhenFormatting)
    {
        $this->hasNationalPrefixOptionalWhenFormatting = true;
        $this->nationalPrefixOptionalWhenFormatting = $nationalPrefixOptionalWhenFormatting;
    }

    /**
     * @return boolean
     */
    public function hasFormat()
    {
        return $this->hasFormat;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $value
     * @return NumberFormat
     */
    public function setFormat($value)
    {
        $this->hasFormat = true;
        $this->format = $value;

        return $this;
    }

    /**
     * @return string[]
     */
    public function leadingDigitPatterns()
    {
        return $this->leadingDigitsPattern;
    }

    /**
     * @return int
     */
    public function leadingDigitsPatternSize()
    {
        return count($this->leadingDigitsPattern);
    }

    /**
     * @param int $index
     * @return string
     */
    public function getLeadingDigitsPattern($index)
    {
        return $this->leadingDigitsPattern[$index];
    }

    /**
     * @param string $value
     * @return NumberFormat
     */
    public function addLeadingDigitsPattern($value)
    {
        $this->leadingDigitsPattern[] = $value;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasNationalPrefixFormattingRule()
    {
        return $this->hasNationalPrefixFormattingRule;
    }

    /**
     * @return string
     */
    public function getNationalPrefixFormattingRule()
    {
        return $this->nationalPrefixFormattingRule;
    }

    /**
     * @param string $value
     * @return NumberFormat
     */
    public function setNationalPrefixFormattingRule($value)
    {
        $this->hasNationalPrefixFormattingRule = true;
        $this->nationalPrefixFormattingRule = $value;

        return $this;
    }

    /**
     * @return NumberFormat
     */
    public function clearNationalPrefixFormattingRule()
    {
        $this->nationalPrefixFormattingRule = null;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasDomesticCarrierCodeFormattingRule()
    {
        return $this->hasDomesticCarrierCodeFormattingRule;
    }

    /**
     * @return string
     */
    public function getDomesticCarrierCodeFormattingRule()
    {
        return $this->domesticCarrierCodeFormattingRule;
    }

    /**
     * @param string $value
     * @return NumberFormat
     */
    public function setDomesticCarrierCodeFormattingRule($value)
    {
        $this->hasDomesticCarrierCodeFormattingRule = true;
        $this->domesticCarrierCodeFormattingRule = $value;

        return $this;
    }

    /**
     * @param NumberFormat $other
     * @return NumberFormat
     */
    public function mergeFrom(NumberFormat $other)
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

    /**
     * @return array
     */
    public function toArray()
    {
        $output = array();
        $output['pattern'] = $this->getPattern();
        $output['format'] = $this->getFormat();

        $output['leadingDigitsPatterns'] = $this->leadingDigitPatterns();

        if ($this->hasNationalPrefixFormattingRule()) {
            $output['nationalPrefixFormattingRule'] = $this->getNationalPrefixFormattingRule();
        }

        if ($this->hasDomesticCarrierCodeFormattingRule()) {
            $output['domesticCarrierCodeFormattingRule'] = $this->getDomesticCarrierCodeFormattingRule();
        }

        if ($this->hasNationalPrefixOptionalWhenFormatting()) {
            $output['nationalPrefixOptionalWhenFormatting'] = $this->getNationalPrefixOptionalWhenFormatting();
        }

        return $output;
    }

    /**
     * @param array $input
     */
    public function fromArray(array $input)
    {
        $this->setPattern($input['pattern']);
        $this->setFormat($input['format']);
        foreach ($input['leadingDigitsPatterns'] as $leadingDigitsPattern) {
            $this->addLeadingDigitsPattern($leadingDigitsPattern);
        }

        if (isset($input['nationalPrefixFormattingRule']) && $input['nationalPrefixFormattingRule'] !== '') {
            $this->setNationalPrefixFormattingRule($input['nationalPrefixFormattingRule']);
        }
        if (isset($input['domesticCarrierCodeFormattingRule']) && $input['domesticCarrierCodeFormattingRule'] !== '') {
            $this->setDomesticCarrierCodeFormattingRule($input['domesticCarrierCodeFormattingRule']);
        }

        if (isset($input['nationalPrefixOptionalWhenFormatting'])) {
            $this->setNationalPrefixOptionalWhenFormatting($input['nationalPrefixOptionalWhenFormatting']);
        }
    }
}
