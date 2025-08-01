<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Bundles;

class SocialSharingBundle extends Bundle {
	/**
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->l10n->t('Social sharing bundle');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAppIdentifiers() {
		return [
			'socialsharing_twitter',
			'socialsharing_facebook',
			'socialsharing_email',
			'socialsharing_diaspora',
		];
	}
}
