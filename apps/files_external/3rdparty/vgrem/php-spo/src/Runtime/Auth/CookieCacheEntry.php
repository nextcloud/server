<?php


namespace Office365\PHP\Client\Runtime\Auth;

use DateTime;

class CookieCacheEntry
{

    public function isValid(){
        $now = new DateTime('NOW');
        return $now < $this->Expires;
    }

    /**
     * @var string
     */
    public $Cookie;

    /**
     * @var DateTime
     */
    public $Expires;

}