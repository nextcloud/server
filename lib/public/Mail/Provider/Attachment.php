<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @author Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Mail\Provider;

/**
 * Mail Attachment Object
 *
 * This object is used to define the parameters of a mail attachment
 *
 * @since 30.0.0
 *
 */
class Attachment implements \OCP\Mail\Provider\IAttachment {

	/**
	 * initialize the mail attachment object
	 *
	 * @since 30.0.0
	 *
	 * @param string|null $contents		binary contents of file
	 * @param string|null $name			file name (e.g example.txt)
	 * @param string|null $type			mime type (e.g. text/plain)
	 * @param bool $embedded			embedded status of the attachment, default is false
	 */
	public function __construct(
		protected ?string $contents,
		protected ?string $name,
		protected ?string $type,
		protected bool $embedded = false
	) {
	}

	/**
	 * sets the attachment file name
	 *
	 * @since 30.0.0
	 *
	 * @param string $value     file name (e.g example.txt)
	 *
	 * @return self             return this object for command chaining
	 */
	public function setName(string $value): self {
		$this->name = $value;
		return $this;
	}

	/**
	 * gets the attachment file name
	 *
	 * @since 30.0.0
	 *
	 * @return string | null	returns the attachment file name or null if not set
	 */
	public function getName(): string | null {
		return $this->name;
	}

	/**
	 * sets the attachment mime type
	 *
	 * @since 30.0.0
	 *
	 * @param string $value     mime type (e.g. text/plain)
	 *
	 * @return self             return this object for command chaining
	 */
	public function setType(string $value): self {
		$this->type = $value;
		return $this;
	}

	/**
	 * gets the attachment mime type
	 *
	 * @since 30.0.0
	 *
	 * @return string | null	returns the attachment mime type or null if not set
	 */
	public function getType(): string | null {
		return $this->type;
	}

	/**
	 * sets the attachment contents (actual data)
	 *
	 * @since 30.0.0
	 *
	 * @param string $value     binary contents of file
	 *
	 * @return self             return this object for command chaining
	 */
	public function setContents(string $value): self {
		$this->contents = $value;
		return $this;
	}

	/**
	 * gets the attachment contents (actual data)
	 *
	 * @since 30.0.0
	 *
	 * @return string | null	returns the attachment contents or null if not set
	 */
	public function getContents(): string | null {
		return $this->contents;
	}

	/**
	 * sets the embedded status of the attachment
	 *
	 * @since 30.0.0
	 *
	 * @param bool $value		true - embedded / false - not embedded
	 *
	 * @return self             return this object for command chaining
	 */
	public function setEmbedded(bool $value): self {
		$this->embedded = $value;
		return $this;
	}

	/**
	 * gets the embedded status of the attachment
	 *
	 * @since 30.0.0
	 *
	 * @return bool			embedded status of the attachment
	 */
	public function getEmbedded(): bool {
		return $this->embedded;
	}

}
