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

namespace OC\Command;

use OCP\Command\IConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class SymfonyConfigurationAdapter implements IConfiguration {

	private Command $command;

	public function __construct(Command $command) {
		$this->command = $command;
	}

	public function addArgument(string $name,
								bool $required = false,
								string $description = '',
								$default = null): void {
		$this->command->addArgument(
			$name,
			$required ? InputArgument::REQUIRED : InputArgument::OPTIONAL,
			$description,
			$default
		);
	}

}
