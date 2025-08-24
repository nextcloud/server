<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Template;

use OCP\EventDispatcher\Event;

/**
 * @since 30.0.0
 */
class BeforeGetTemplatesEvent extends Event {
	/** @var array<Template> */
	private array $templates;
	/** @var bool */
	private bool $withFields;

	/**
	 * @param array<Template> $templates
	 *
	 * @since 30.0.0
	 */
	public function __construct(array $templates, bool $withFields = false) {
		parent::__construct();

		$this->templates = $templates;
		$this->withFields = $withFields;
	}

	/**
	 * @return array<Template>
	 *
	 * @since 30.0.0
	 */
	public function getTemplates(): array {
		return $this->templates;
	}

	/**
	 * @return bool
	 *
	 * @since 32.0.0
	 */
	public function shouldGetFields(): bool {
		return $this->withFields;
	}
}
