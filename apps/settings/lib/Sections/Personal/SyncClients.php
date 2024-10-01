<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Sections\Personal;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class SyncClients implements IIconSection {

	/** @var IL10N */
	private $l;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(IL10N $l, IURLGenerator $urlGenerator) {
		$this->l = $l;
		$this->urlGenerator = $urlGenerator;
	}

	public function getIcon() {
		return $this->urlGenerator->imagePath('core', 'clients/phone.svg');
	}

	public function getID(): string {
		return 'sync-clients';
	}

	public function getName(): string {
		return $this->l->t('Mobile & desktop');
	}

	public function getPriority(): int {
		return 15;
	}
}
