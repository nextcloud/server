<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		private ?string $path = null
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
