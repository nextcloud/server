<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Sections\Admin;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Delegation implements IIconSection {
	/**
	 * @param IURLGenerator $url
	 * @param IL10N $l
	 */
	public function __construct(
		private IURLGenerator $url,
		private IL10N $l,
	) {
	}

	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getID() {
		return 'admindelegation';
	}

	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getName() {
		return $this->l->t('Administration privileges');
	}

	/**
	 * {@inheritdoc}
	 * @return int
	 */
	public function getPriority() {
		return 54;
	}

	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getIcon() {
		return $this->url->imagePath('core', 'actions/user-admin.svg');
	}
}
