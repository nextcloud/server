<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Activity;

use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

class Filter implements IFilter {
	public function __construct(
		protected IL10N $l,
		protected IURLGenerator $url,
	) {
	}

	public function getIdentifier(): string {
		return 'comments';
	}

	public function getName(): string {
		return $this->l->t('Comments');
	}

	public function getPriority(): int {
		return 40;
	}

	public function getIcon(): string {
		return $this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/comment.svg'));
	}

	/**
	 * @param string[] $types
	 * @return string[] An array of allowed apps from which activities should be displayed
	 */
	public function filterTypes(array $types): array {
		return $types;
	}

	/**
	 * @return string[] An array of allowed apps from which activities should be displayed
	 */
	public function allowedApps(): array {
		return ['comments'];
	}
}
