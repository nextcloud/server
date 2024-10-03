<?php

namespace OCA\SocialLogin\Settings;

use OCP\Settings\IIconSection;
use OCP\IURLGenerator;
use OCP\IL10N;

class AdminSection implements IIconSection
{
    /** @var string */
    private $appName;
    /** @var IL10N */
    private $l;
    /** @var IURLGenerator */
    private $urlGenerator;


    public function __construct($appName, IL10N $l, IURLGenerator $urlGenerator) {
        $this->l = $l;
        $this->appName = $appName;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * returns the ID of the section. It is supposed to be a lower case string
     *
     * @returns string
     */
    public function getID() {
        return $this->appName; //or a generic id if feasible
    }

    /**
     * returns the translated name as it should be displayed, e.g. 'LDAP / AD
     * integration'. Use the L10N service to translate it.
     *
     * @return string
     */
    public function getName() {
        return $this->l->t('Social login');
    }

    /**
     * @return int whether the form should be rather on the top or bottom of
     * the settings navigation. The sections are arranged in ascending order of
     * the priority values. It is required to return a value between 0 and 99.
     */
    public function getPriority() {
        return 5;
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon() {
        return $this->urlGenerator->imagePath('core', 'categories/social.svg');
    }
}
