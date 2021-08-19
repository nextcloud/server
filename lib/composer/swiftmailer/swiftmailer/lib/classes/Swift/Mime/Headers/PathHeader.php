<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

/**
 * A Path Header in Swift Mailer, such a Return-Path.
 *
 * @author Chris Corbyn
 */
class Swift_Mime_Headers_PathHeader extends Swift_Mime_Headers_AbstractHeader
{
    /**
     * The address in this Header (if specified).
     *
     * @var string
     */
    private $address;

    /**
     * The strict EmailValidator.
     *
     * @var EmailValidator
     */
    private $emailValidator;

    private $addressEncoder;

    /**
     * Creates a new PathHeader with the given $name.
     *
     * @param string $name
     */
    public function __construct($name, EmailValidator $emailValidator, Swift_AddressEncoder $addressEncoder = null)
    {
        $this->setFieldName($name);
        $this->emailValidator = $emailValidator;
        $this->addressEncoder = $addressEncoder ?? new Swift_AddressEncoder_IdnAddressEncoder();
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
        return self::TYPE_PATH;
    }

    /**
     * Set the model for the field body.
     * This method takes a string for an address.
     *
     * @param string $model
     *
     * @throws Swift_RfcComplianceException
     */
    public function setFieldBodyModel($model)
    {
        $this->setAddress($model);
    }

    /**
     * Get the model for the field body.
     * This method returns a string email address.
     *
     * @return mixed
     */
    public function getFieldBodyModel()
    {
        return $this->getAddress();
    }

    /**
     * Set the Address which should appear in this Header.
     *
     * @param string $address
     *
     * @throws Swift_RfcComplianceException
     */
    public function setAddress($address)
    {
        if (null === $address) {
            $this->address = null;
        } elseif ('' == $address) {
            $this->address = '';
        } else {
            $this->assertValidAddress($address);
            $this->address = $address;
        }
        $this->setCachedValue(null);
    }

    /**
     * Get the address which is used in this Header (if any).
     *
     * Null is returned if no address is set.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
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
            if (isset($this->address)) {
                $address = $this->addressEncoder->encodeString($this->address);
                $this->setCachedValue('<'.$address.'>');
            }
        }

        return $this->getCachedValue();
    }

    /**
     * Throws an Exception if the address passed does not comply with RFC 2822.
     *
     * @param string $address
     *
     * @throws Swift_RfcComplianceException If address is invalid
     */
    private function assertValidAddress($address)
    {
        if (!$this->emailValidator->isValid($address, new RFCValidation())) {
            throw new Swift_RfcComplianceException('Address set in PathHeader does not comply with addr-spec of RFC 2822.');
        }
    }
}
