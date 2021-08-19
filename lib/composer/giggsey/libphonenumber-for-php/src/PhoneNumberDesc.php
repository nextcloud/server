<?php

namespace libphonenumber;

/**
 * Phone Number Description
 */
class PhoneNumberDesc
{
    protected $hasNationalNumberPattern = false;
    protected $nationalNumberPattern = '';
    protected $hasExampleNumber = false;
    protected $exampleNumber = '';
    /**
     * @var array
     */
    protected $possibleLength;
    /**
     * @var array
     */
    protected $possibleLengthLocalOnly;

    public function __construct()
    {
        $this->clear();
    }

    /**
     * @return PhoneNumberDesc
     */
    public function clear()
    {
        $this->clearNationalNumberPattern();
        $this->clearPossibleLength();
        $this->clearPossibleLengthLocalOnly();
        $this->clearExampleNumber();

        return $this;
    }

    /**
     * @return array
     */
    public function getPossibleLength()
    {
        return $this->possibleLength;
    }

    /**
     * @param array $possibleLength
     */
    public function setPossibleLength($possibleLength)
    {
        $this->possibleLength = $possibleLength;
    }

    public function addPossibleLength($possibleLength)
    {
        if (!in_array($possibleLength, $this->possibleLength)) {
            $this->possibleLength[] = $possibleLength;
        }
    }

    public function clearPossibleLength()
    {
        $this->possibleLength = array();
    }

    /**
     * @return array
     */
    public function getPossibleLengthLocalOnly()
    {
        return $this->possibleLengthLocalOnly;
    }

    /**
     * @param array $possibleLengthLocalOnly
     */
    public function setPossibleLengthLocalOnly($possibleLengthLocalOnly)
    {
        $this->possibleLengthLocalOnly = $possibleLengthLocalOnly;
    }

    public function addPossibleLengthLocalOnly($possibleLengthLocalOnly)
    {
        if (!in_array($possibleLengthLocalOnly, $this->possibleLengthLocalOnly)) {
            $this->possibleLengthLocalOnly[] = $possibleLengthLocalOnly;
        }
    }

    public function clearPossibleLengthLocalOnly()
    {
        $this->possibleLengthLocalOnly = array();
    }

    /**
     * @return boolean
     */
    public function hasNationalNumberPattern()
    {
        return $this->hasNationalNumberPattern;
    }

    /**
     * @return string
     */
    public function getNationalNumberPattern()
    {
        return $this->nationalNumberPattern;
    }

    /**
     * @param string $value
     * @return PhoneNumberDesc
     */
    public function setNationalNumberPattern($value)
    {
        $this->hasNationalNumberPattern = true;
        $this->nationalNumberPattern = $value;

        return $this;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function clearNationalNumberPattern()
    {
        $this->hasNationalNumberPattern = false;
        $this->nationalNumberPattern = '';
        return $this;
    }

    /**
     * @return string
     */
    public function hasExampleNumber()
    {
        return $this->hasExampleNumber;
    }

    /**
     * @return string
     */
    public function getExampleNumber()
    {
        return $this->exampleNumber;
    }

    /**
     * @param string $value
     * @return PhoneNumberDesc
     */
    public function setExampleNumber($value)
    {
        $this->hasExampleNumber = true;
        $this->exampleNumber = $value;

        return $this;
    }

    /**
     * @return PhoneNumberDesc
     */
    public function clearExampleNumber()
    {
        $this->hasExampleNumber = false;
        $this->exampleNumber = '';

        return $this;
    }

    /**
     * @param PhoneNumberDesc $other
     * @return PhoneNumberDesc
     */
    public function mergeFrom(PhoneNumberDesc $other)
    {
        if ($other->hasNationalNumberPattern()) {
            $this->setNationalNumberPattern($other->getNationalNumberPattern());
        }
        if ($other->hasExampleNumber()) {
            $this->setExampleNumber($other->getExampleNumber());
        }
        $this->setPossibleLength($other->getPossibleLength());
        $this->setPossibleLengthLocalOnly($other->getPossibleLengthLocalOnly());

        return $this;
    }

    /**
     * @param PhoneNumberDesc $other
     * @return boolean
     */
    public function exactlySameAs(PhoneNumberDesc $other)
    {
        return $this->nationalNumberPattern === $other->nationalNumberPattern &&
        $this->exampleNumber === $other->exampleNumber;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = array();
        if ($this->hasNationalNumberPattern()) {
            $data['NationalNumberPattern'] = $this->getNationalNumberPattern();
        }
        if ($this->hasExampleNumber()) {
            $data['ExampleNumber'] = $this->getExampleNumber();
        }

        $data['PossibleLength'] = $this->getPossibleLength();
        $data['PossibleLengthLocalOnly'] = $this->getPossibleLengthLocalOnly();

        return $data;
    }

    /**
     * @param array $input
     * @return PhoneNumberDesc
     */
    public function fromArray(array $input)
    {
        if (isset($input['NationalNumberPattern']) && $input['NationalNumberPattern'] != '') {
            $this->setNationalNumberPattern($input['NationalNumberPattern']);
        }
        if (isset($input['ExampleNumber']) && $input['NationalNumberPattern'] != '') {
            $this->setExampleNumber($input['ExampleNumber']);
        }
        $this->setPossibleLength($input['PossibleLength']);
        $this->setPossibleLengthLocalOnly($input['PossibleLengthLocalOnly']);

        return $this;
    }
}
