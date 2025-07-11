<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Bundles;

class GroupwareBundle extends Bundle {
	/**
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->l10n->t('Groupware bundle');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAppIdentifiers() {
		return [
			'calendar',
			'contacts',
			'deck',
			'mail'
		];
	}
}
