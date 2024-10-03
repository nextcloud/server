<?php

namespace HybridauthTest\Hybridauth\User;

use Hybridauth\User\Profile;

class ProfileTest extends \PHPUnit\Framework\TestCase
{
    public function test_instance_of()
    {
        $profile = new Profile();

        $this->assertInstanceOf('\\Hybridauth\\User\\Profile', $profile);
    }

    public function test_has_attributes()
    {
        $profile_class = '\\Hybridauth\\User\\Profile';

        $this->assertClassHasAttribute('identifier', $profile_class);
        $this->assertClassHasAttribute('webSiteURL', $profile_class);
        $this->assertClassHasAttribute('profileURL', $profile_class);
        $this->assertClassHasAttribute('photoURL', $profile_class);
        $this->assertClassHasAttribute('displayName', $profile_class);
        $this->assertClassHasAttribute('firstName', $profile_class);
        $this->assertClassHasAttribute('lastName', $profile_class);
        $this->assertClassHasAttribute('description', $profile_class);
        $this->assertClassHasAttribute('gender', $profile_class);
        $this->assertClassHasAttribute('language', $profile_class);
        $this->assertClassHasAttribute('age', $profile_class);
        $this->assertClassHasAttribute('birthDay', $profile_class);
        $this->assertClassHasAttribute('birthMonth', $profile_class);
        $this->assertClassHasAttribute('birthYear', $profile_class);
        $this->assertClassHasAttribute('email', $profile_class);
        $this->assertClassHasAttribute('emailVerified', $profile_class);
        $this->assertClassHasAttribute('phone', $profile_class);
        $this->assertClassHasAttribute('address', $profile_class);
        $this->assertClassHasAttribute('country', $profile_class);
        $this->assertClassHasAttribute('region', $profile_class);
        $this->assertClassHasAttribute('city', $profile_class);
        $this->assertClassHasAttribute('zip', $profile_class);
    }

    public function test_set_attributes()
    {
        $profile = new Profile();

        $profile->identifier = true;
        $profile->webSiteURL = true;
        $profile->profileURL = true;
        $profile->photoURL = true;
        $profile->displayName = true;
        $profile->firstName = true;
        $profile->lastName = true;
        $profile->description = true;
        $profile->gender = true;
        $profile->language = true;
        $profile->age = true;
        $profile->birthDay = true;
        $profile->birthMonth = true;
        $profile->birthYear = true;
        $profile->email = true;
        $profile->emailVerified = true;
        $profile->phone = true;
        $profile->address = true;
        $profile->country = true;
        $profile->region = true;
        $profile->city = true;
        $profile->zip = true;
    }

    /**
     * @expectedException \Hybridauth\Exception\UnexpectedValueException
     */
    public function test_property_overloading()
    {
        $profile = new Profile();
        $profile->slug = true;
    }
}
