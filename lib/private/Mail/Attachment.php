<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Mail;

use OCP\Mail\IAttachment;
use Symfony\Component\Mime\Email;

/**
 * Class Attachment
 *
 * @package OC\Mail
 * @since 13.0.0
 */
class Attachment implements IAttachment {
	public function __construct(
		private ?string $body,
		private ?string $name,
		private ?string $contentType,
		private ?string $path = null,
	) {
	}

	/**
	 * @return $this
	 * @since 13.0.0
	 */
	public function setFilename(string $filename): IAttachment {
		$this->name = $filename;
		return $this;
	}

	/**
	 * @return $this
	 * @since 13.0.0
	 */
	public function setContentType(string $contentType): IAttachment {
		$this->contentType = $contentType;
		return $this;
	}

	/**
	 * @return $this
	 * @since 13.0.0
	 */
	public function setBody(string $body): IAttachment {
		$this->body = $body;
		return $this;
	}

	public function attach(Email $symfonyEmail): void {
		if ($this->path !== null) {
			$symfonyEmail->attachFromPath($this->path, $this->name, $this->contentType);
		} else {
			$symfonyEmail->attach($this->body, $this->name, $this->contentType);
		}
	}
}
