<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\Talk;

use OCP\Talk\IConversationOptions;

class ConversationOptions implements IConversationOptions {
	private bool $isPublic;

	private function __construct(bool $isPublic) {
		$this->isPublic = $isPublic;
	}

	public static function default(): self {
		return new self(false);
	}

	public function setPublic(bool $isPublic = true): IConversationOptions {
		$this->isPublic = $isPublic;
		return $this;
	}

	public function isPublic(): bool {
		return $this->isPublic;
	}
}
