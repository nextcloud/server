<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\User;

use Hybridauth\Exception\UnexpectedValueException;

/**
 * Hybridauth\Userobject represents the current logged in user profile.
 */
final class Profile
{
    /**
     * The Unique user's ID on the connected provider
     *
     * @var int|null
     */
    public $identifier = null;

    /**
     * User website, blog, web page
     *
     * @var string|null
     */
    public $webSiteURL = null;

    /**
     * URL link to profile page on the IDp web site
     *
     * @var string|null
     */
    public $profileURL = null;

    /**
     * URL link to user photo or avatar
     *
     * @var string|null
     */
    public $photoURL = null;

    /**
     * User displayName provided by the IDp or a concatenation of first and last name.
     *
     * @var string|null
     */
    public $displayName = null;

    /**
     * A short about_me
     *
     * @var string|null
     */
    public $description = null;

    /**
     * User's first name
     *
     * @var string|null
     */
    public $firstName = null;

    /**
     * User's last name
     *
     * @var string|null
     */
    public $lastName = null;

    /**
     * male or female
     *
     * @var string|null
     */
    public $gender = null;

    /**
     * Language
     *
     * @var string|null
     */
    public $language = null;

    /**
     * User age, we don't calculate it. we return it as is if the IDp provide it.
     *
     * @var int|null
     */
    public $age = null;

    /**
     * User birth Day
     *
     * @var int|null
     */
    public $birthDay = null;

    /**
     * User birth Month
     *
     * @var int|null
     */
    public $birthMonth = null;

    /**
     * User birth Year
     *
     * @var int|null
     */
    public $birthYear = null;

    /**
     * User email. Note: not all of IDp grant access to the user email
     *
     * @var string|null
     */
    public $email = null;

    /**
     * Verified user email. Note: not all of IDp grant access to verified user email
     *
     * @var string|null
     */
    public $emailVerified = null;

    /**
     * Phone number
     *
     * @var string|null
     */
    public $phone = null;

    /**
     * Complete user address
     *
     * @var string|null
     */
    public $address = null;

    /**
     * User country
     *
     * @var string|null
     */
    public $country = null;

    /**
     * Region
     *
     * @var string|null
     */
    public $region = null;

    /**
     * City
     *
     * @var string|null
     */
    public $city = null;

    /**
     * Postal code
     *
     * @var string|null
     */
    public $zip = null;

    /**
     * An extra data which is related to the user
     *
     * @var array
     */
    public $data = [];

    /**
     * Prevent the providers adapters from adding new fields.
     *
     * @throws UnexpectedValueException
     * @var mixed $value
     *
     * @var string $name
     */
    public function __set($name, $value)
    {
        throw new UnexpectedValueException(sprintf('Adding new property "%s" to %s is not allowed.', $name, __CLASS__));
    }
}
