<?php

namespace libphonenumber;

class PhoneNumberMatch
{
    /**
     * The start index into the text.
     * @var int
     */
    private $start;

    /**
     * The raw substring matched.
     * @var string
     */
    private $rawString;

    /**
     * The matched phone number.
     * @var PhoneNumber
     */
    private $number;

    /**
     * Creates a new match
     *
     * @param int $start The start index into the target text
     * @param string $rawString The matched substring of the target text
     * @param PhoneNumber $number The matched phone number
     * @throws \NullPointerException
     */
    public function __construct($start, $rawString, PhoneNumber $number)
    {
        if ($start < 0) {
            throw new \InvalidArgumentException('Start index must be >= 0.');
        }

        if ($rawString === null) {
            throw new \NullPointerException;
        }

        $this->start = $start;
        $this->rawString = $rawString;
        $this->number = $number;
    }

    /**
     * Returns the phone number matched by the receiver.
     * @return PhoneNumber
     */
    public function number()
    {
        return $this->number;
    }

    /**
     * Returns the start index of the matched phone number within the searched text.
     * @return int
     */
    public function start()
    {
        return $this->start;
    }

    /**
     * Returns the exclusive end index of the matched phone number within the searched text.
     * @return int
     */
    public function end()
    {
        return $this->start + \mb_strlen($this->rawString);
    }

    /**
     * Returns the raw string matched as a phone number in the searched text.
     * @return string
     */
    public function rawString()
    {
        return $this->rawString;
    }

    public function __toString()
    {
        return "PhoneNumberMatch [{$this->start()},{$this->end()}) {$this->rawString}";
    }
}
