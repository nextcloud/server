<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Template;

use OCP\EventDispatcher\Event;

/**
 * @since 30.0.0
 */
class RegisterTemplateCreatorEvent extends Event {

	/**
	 * @since 30.0.0
	 */
	public function __construct(
		private ITemplateManager $templateManager,
	) {
	}

	/**
	 * @since 30.0.0
	 */
	public function getTemplateManager(): ITemplateManager {
		return $this->templateManager;
	}
}
