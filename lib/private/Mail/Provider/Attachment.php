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
namespace OC\Mail\Provider;

class Attachment implements \OCP\Mail\Provider\IAttachment {

    public function __construct(
        protected ?string $contents,
		protected ?string $name,
		protected ?string $type,
        protected bool $inline = false
    ) {
    }

    /**
	 * @return $this
	 * @since 30.0.0
	 */
	public function setName(string $value): self {
		$this->name = $value;
		return $this;
    }

    /**
	 * @return string | null
	 * @since 30.0.0
	 */
	public function getName(): string | null {
		return $this->name;
	}

	/**
	 * @return $this
	 * @since 30.0.0
	 */
	public function setType(string $value): self {
		$this->type = $value;
		return $this;
	}

    /**
	 * @return string | null
	 * @since 30.0.0
	 */
	public function getType(): string | null {
		return $this->type;
	}

	/**
	 * @return $this
	 * @since 30.0.0
	 */
	public function setContents(string $value): self {
		$this->contents = $value;
		return $this;
	}

    /**
	 * @return string | null
	 * @since 30.0.0
	 */
	public function getContents(): string | null {
		return $this->contents;
	}

    /**
	 * @return $this
	 * @since 30.0.0
	 */
	public function setInline(bool $value): self {
		$this->inline = $value;
		return $this;
	}

    /**
	 * @return string | null
	 * @since 30.0.0
	 */
	public function getInline(): bool | null {
		return $this->inline;
	}

}
