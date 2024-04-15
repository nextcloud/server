<?php
/**
 * @copyright Copyright (c) 2016 Kareem <yemkareems@gmail.com>
 *
 * @author Kareem <yemkareems@gmail.com>
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
			->setName('user:clear-avatar-cache')
			->setDescription('clear avatar cache');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$output->writeln("cleared avatar cache started");
		$this->avatarManager->clearCachedAvatars();
		$output->writeln("cleared avatar cache successfully");
		return 0;
	}
}
