<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Template;

use OCP\EventDispatcher\Event;
use OCP\Files\File;

/**
 * @since 21.0.0
 */
class FileCreatedFromTemplateEvent extends Event {
	private $template;
	private $target;

	/**
	 * @param File|null $template
	 * @param File $target
	 * @since 21.0.0
	 */
	public function __construct(?File $template, File $target) {
		$this->template = $template;
		$this->target = $target;
	}

	/**
	 * @return File|null
	 * @since 21.0.0
	 */
	public function getTemplate(): ?File {
		return $this->template;
	}

	/**
	 * @return File
	 * @since 21.0.0
	 */
	public function getTarget(): File {
		return $this->target;
	}
}
