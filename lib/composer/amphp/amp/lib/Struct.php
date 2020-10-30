<?php

namespace Amp;

/**
 * A "safe" struct trait for public property aggregators.
 *
 * This trait is intended to make using public properties a little safer by throwing when
 * nonexistent property names are read or written.
 */
trait Struct
{
    /**
     * The minimum percentage [0-100] at which to recommend a similar property
     * name when generating error messages.
     */
    private $__propertySuggestThreshold = 70;

    /**
     * @param string $property
     *
     * @psalm-return no-return
     */
    public function __get(string $property)
    {
        throw new \Error(
            $this->generateStructPropertyError($property)
        );
    }

    /**
     * @param string $property
     * @param mixed  $value
     *
     * @psalm-return no-return
     */
    public function __set(string $property, $value)
    {
        throw new \Error(
            $this->generateStructPropertyError($property)
        );
    }

    private function generateStructPropertyError(string $property): string
    {
        $suggestion = $this->suggestPropertyName($property);
        $suggestStr = ($suggestion == "") ? "" : " ... did you mean \"{$suggestion}?\"";

        return \sprintf(
            "%s property \"%s\" does not exist%s",
            \str_replace("\0", "@", \get_class($this)), // Handle anonymous class names.
            $property,
            $suggestStr
        );
    }

    private function suggestPropertyName(string $badProperty): string
    {
        $badProperty = \strtolower($badProperty);
        $bestMatch = "";
        $bestMatchPercentage = 0;

        /** @psalm-suppress RawObjectIteration */
        foreach ($this as $property => $value) {
            // Never suggest properties that begin with an underscore
            if ($property[0] === "_") {
                continue;
            }
            \similar_text($badProperty, \strtolower($property), $byRefPercentage);
            if ($byRefPercentage > $bestMatchPercentage) {
                $bestMatchPercentage = $byRefPercentage;
                $bestMatch = $property;
            }
        }

        return ($bestMatchPercentage >= $this->__propertySuggestThreshold) ? $bestMatch : "";
    }
}
