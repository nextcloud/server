<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A Date MIME Header for Swift Mailer.
 *
 * @author Chris Corbyn
 */
class Swift_Mime_Headers_DateHeader extends Swift_Mime_Headers_AbstractHeader
{
    /**
     * Date-time value of this Header.
     *
     * @var DateTimeImmutable
     */
    private $dateTime;

    /**
     * Creates a new DateHeader with $name.
     *
     * @param string $name of Header
     */
    public function __construct($name)
    {
        $this->setFieldName($name);
    }

    /**
     * Get the type of Header that this instance represents.
     *
     * @see TYPE_TEXT, TYPE_PARAMETERIZED, TYPE_MAILBOX
     * @see TYPE_DATE, TYPE_ID, TYPE_PATH
     *
     * @return int
     */
    public function getFieldType()
    {
        return self::TYPE_DATE;
    }

    /**
     * Set the model for the field body.
     *
     * @param DateTimeInterface $model
     */
    public function setFieldBodyModel($model)
    {
        $this->setDateTime($model);
    }

    /**
     * Get the model for the field body.
     *
     * @return DateTimeImmutable
     */
    public function getFieldBodyModel()
    {
        return $this->getDateTime();
    }

    /**
     * Get the date-time representing the Date in this Header.
     *
     * @return DateTimeImmutable
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Set the date-time of the Date in this Header.
     *
     * If a DateTime instance is provided, it is converted to DateTimeImmutable.
     */
    public function setDateTime(DateTimeInterface $dateTime)
    {
        $this->clearCachedValueIf($this->getCachedValue() != $dateTime->format(DateTime::RFC2822));
        if ($dateTime instanceof DateTime) {
            $immutable = new DateTimeImmutable('@'.$dateTime->getTimestamp());
            $dateTime = $immutable->setTimezone($dateTime->getTimezone());
        }
        $this->dateTime = $dateTime;
    }

    /**
     * Get the string value of the body in this Header.
     *
     * This is not necessarily RFC 2822 compliant since folding white space will
     * not be added at this stage (see {@link toString()} for that).
     *
     * @see toString()
     *
     * @return string
     */
    public function getFieldBody()
    {
        if (!$this->getCachedValue()) {
            if (isset($this->dateTime)) {
                $this->setCachedValue($this->dateTime->format(DateTime::RFC2822));
            }
        }

        return $this->getCachedValue();
    }
}
