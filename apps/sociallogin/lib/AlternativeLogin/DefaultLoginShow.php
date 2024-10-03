<?php

namespace OCA\SocialLogin\AlternativeLogin;

use OCP\Authentication\IAlternativeLogin;
use OCP\IL10N;
use OCP\Util;

class DefaultLoginShow implements IAlternativeLogin
{
    private $appName;
    /** @var IL10N */
    private $l;

    public function __construct($appName, IL10N $l)
    {
        $this->appName = $appName;
        $this->l = $l;
    }

    public function getLabel(): string
    {
        return $this->l->t('Log in with username or email');
    }

    public function getLink(): string
    {
        return '#body-login';
    }

    public function getClass(): string
    {
        return '';
    }

    public function load(): void
    {
        Util::addStyle($this->appName, 'hide_default_login');
    }
}
