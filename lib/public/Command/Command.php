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

namespace OCP\Command;

use function implode;

abstract class Command {

	public const EXIT_CODE_SUCCESS = 0;
	public const EXIT_CODE_ERROR = 1;

	private string $appId;

	public function __construct(string $appId) {
		$this->appId = $appId;
	}

	public function getNamespace(): ?string {
		return $this->appId;
	}

	public abstract function getName(): string;

	public abstract function getDescription(): string;

	public function getFullyQualifiedName(): string {
		if ($this->getNamespace() === null) {
			return $this->getName();
		}

		return implode(':', [
			$this->getNamespace(),
			$this->getName()
		]);
	}

	public function isEnabled(): bool {
		return true;
	}

	public function configure(IConfiguration $config): void {
	}

	public abstract function execute(IInput $input, IOutput $output);

}
