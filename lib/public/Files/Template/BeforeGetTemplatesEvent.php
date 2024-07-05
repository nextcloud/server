<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Template;

use OCP\EventDispatcher\Event;

class BeforeGetTemplatesEvent extends Event {
	private array $templates;

	public function __construct(array $templates) {
		parent::__construct();

		$this->templates = $templates;
	}

	public function getTemplates(): array {
		return $this->templates;
	}
}
