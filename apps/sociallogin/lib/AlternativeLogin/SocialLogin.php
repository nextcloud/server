<?php

namespace OCA\SocialLogin\AlternativeLogin;

use OCP\Authentication\IAlternativeLogin;

class SocialLogin implements IAlternativeLogin
{
    private $label = '';
    private $link = '';
    private $cssClass = '';
    private static $logins = [];

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getClass(): string
    {
        return $this->cssClass;
    }

    public function load(): void
    {
        list($this->label, $this->link, $this->cssClass) = array_shift(self::$logins);
    }

    public static function addLogin($label, $link, $cssClass = '')
    {
        self::$logins[] = [$label, $link, $cssClass];
    }
}
