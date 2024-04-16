<?php
/**
 * @copyright Copyright (c) 2024, Nextcloud GmbH
 *
 * @author Kareem <yemkareems@gmail.com>
 * @license AGPL-3.0-or-later
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
namespace OC\Core\Command\User;

use OC\Avatar\AvatarManager;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearGeneratedAvatarCacheCommand extends Base {
	public function __construct(
		protected AvatarManager $avatarManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setDescription('clear avatar cache')
			->setName('user:clear-avatar-cache');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$output->writeln("Clearing avatar cache has started");
		$this->avatarManager->clearCachedAvatars();
		$output->writeln("Cleared avatar cache successfully");
		return 0;
	}
}
