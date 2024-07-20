<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class PersonalSection implements IIconSection {

	/** @var string */
	protected $appName;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IL10N */
	private $l;

	/**
	 * Personal Section constructor.
	 *
	 * @param string $appName
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $l
	 */
	public function __construct(string $appName,
		IURLGenerator $urlGenerator,
		IL10N $l) {
		$this->appName = $appName;
		$this->urlGenerator = $urlGenerator;
		$this->l = $l;
	}

	/**
	 * returns the relative path to an 16*16 icon describing the section.
	 * e.g. '/core/img/places/files.svg'
	 *
	 * @returns string
	 * @since 13.0.0
	 */
	public function getIcon() {
		return $this->urlGenerator->imagePath($this->appName, 'accessibility-dark.svg');
	}

	/**
	 * returns the ID of the section. It is supposed to be a lower case string,
	 * e.g. 'ldap'
	 *
	 * @returns string
	 * @since 9.1
	 */
	public function getID() {
		return $this->appName;
	}

	/**
	 * returns the translated name as it should be displayed, e.g. 'LDAP / AD
	 * integration'. Use the L10N service to translate it.
	 *
	 * @return string
	 * @since 9.1
	 */
	public function getName() {
		return $this->l->t('Appearance and accessibility');
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the settings navigation. The sections are arranged in ascending order of
	 * the priority values. It is required to return a value between 0 and 99.
	 *
	 * E.g.: 70
	 * @since 9.1
	 */
	public function getPriority() {
		return 15;
	}
}
