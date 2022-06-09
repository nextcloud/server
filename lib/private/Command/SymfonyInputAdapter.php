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

use OCP\Command\IInput;
use Symfony\Component\Console\Input\InputInterface;

class SymfonyInputAdapter implements IInput {

	private InputInterface $input;

	public function __construct(InputInterface $input) {
		$this->input = $input;
	}

	public function isInteractive(): bool {
		return $this->input->isInteractive();
	}

	public function getArgument(string $name) {
		return $this->input->getArgument($name);
	}

}
