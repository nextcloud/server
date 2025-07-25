<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Bundles;

class EducationBundle extends Bundle {
	/**
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->l10n->t('Education bundle');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAppIdentifiers() {
		return [
			'dashboard',
			'circles',
			'groupfolders',
			'announcementcenter',
			'quota_warning',
			'user_saml',
			'whiteboard',
		];
	}
}
