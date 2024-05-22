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

interface IAttachment {

    /**
	 * @return $this
	 * @since 30.0.0
	 */
	public function setName(string $value): self;

    /**
	 * @return string | null
	 * @since 30.0.0
	 */
	public function getName(): string | null;

	/**
	 * @return $this
	 * @since 30.0.0
	 */
	public function setType(string $value): self;

    /**
	 * @return string | null
	 * @since 30.0.0
	 */
	public function getType(): string | null;

	/**
	 * @return $this
	 * @since 30.0.0
	 */
	public function setContents(string $value): self;

    /**
	 * @return string | null
	 * @since 30.0.0
	 */
	public function getContents(): string | null;

    /**
	 * @return $this
	 * @since 30.0.0
	 */
	public function setInline(bool $value): self;

    /**
	 * @return string | null
	 * @since 30.0.0
	 */
	public function getInline(): bool | null;

}
