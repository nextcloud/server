<?php
/**
 * @copyright Copyright (c) 2021, hosting.de, Johannes Leuker <developers@hosting.de>
 *
 * @author Johannes Leuker <j.leuker@hosting.de>
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
namespace OC\Core\Command\SystemTag;

use OC\Core\Command\Base;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagAlreadyExistsException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Add extends Base {
	protected ISystemTagManager $systemTagManager;

	public function __construct(ISystemTagManager $systemTagManager) {
		$this->systemTagManager = $systemTagManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('tag:add')
			->setDescription('Add new tag')
			->addArgument(
				'name',
				InputArgument::REQUIRED,
				'name of the tag',
			)
			->addArgument(
				'access',
				InputArgument::REQUIRED,
				'access level of the tag (public, restricted or invisible)',
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');
		if ($name === '') {
			$output->writeln('<error>`name` can\'t be empty</error>');
			return 3;
		}

		switch ($input->getArgument('access')) {
			case 'public':
				$userVisible = true;
				$userAssignable = true;
				break;
			case 'restricted':
				$userVisible = true;
				$userAssignable = false;
				break;
			case 'invisible':
				$userVisible = false;
				$userAssignable = false;
				break;
			default:
				$output->writeln('<error>`access` property is invalid</error>');
				return 1;
		}

		try {
			$tag = $this->systemTagManager->createTag($name, $userVisible, $userAssignable);

			$this->writeArrayInOutputFormat($input, $output,
				[
					'id' => $tag->getId(),
					'name' => $tag->getName(),
					'access' => ISystemTag::ACCESS_LEVEL_LOOKUP[$tag->getAccessLevel()],
				]);
			return 0;
		} catch (TagAlreadyExistsException $e) {
			$output->writeln('<error>'.$e->getMessage().'</error>');
			return 2;
		}
	}
}
