<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Activity;

use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

class Filter implements IFilter {
	public const TYPE_REMOTE_SHARE = 'remote_share';
	public const TYPE_SHARED = 'shared';

	public function __construct(
		protected IL10N $l,
		protected IURLGenerator $url,
	) {
	}

	/**
	 * @return string Lowercase a-z only identifier
	 * @since 11.0.0
	 */
	public function getIdentifier() {
		return 'files_sharing';
	}

	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	public function getName() {
		return $this->l->t('File shares');
	}

	/**
	 * @return int
	 * @since 11.0.0
	 */
	public function getPriority() {
		return 31;
	}

	/**
	 * @return string Full URL to an icon, empty string when none is given
	 * @since 11.0.0
	 */
	public function getIcon() {
		return $this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg'));
	}

	/**
	 * @param string[] $types
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function filterTypes(array $types) {
		return array_intersect([
			self::TYPE_SHARED,
			self::TYPE_REMOTE_SHARE,
			'file_downloaded',
		], $types);
	}

	/**
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function allowedApps() {
		return [
			'files_sharing',
			'files_downloadactivity',
		];
	}
}
