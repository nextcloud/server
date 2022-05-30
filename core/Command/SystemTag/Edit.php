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
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagAlreadyExistsException;
use OCP\SystemTag\TagNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Edit extends Base {
	protected ISystemTagManager $systemTagManager;

	public function __construct(ISystemTagManager $systemTagManager) {
		$this->systemTagManager = $systemTagManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('tag:edit')
			->setDescription('edit tag attributes')
			->addArgument(
				'id',
				InputOption::VALUE_REQUIRED,
				'The ID of the tag that should be deleted',
			)
			->addOption(
				'name',
				null,
				InputOption::VALUE_OPTIONAL,
				'sets the \'name\' parameter',
			)
			->addOption(
				'access',
				null,
				InputOption::VALUE_OPTIONAL,
				'sets the access control level (public, restricted, invisible)',
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$tagArray = $this->systemTagManager->getTagsByIds($input->getArgument('id'));
		// returns an array, but we always expect 0 or 1 results

		if (!$tagArray) {
			$output->writeln('<error>Tag not found</error>');
			return 3;
		}

		$tag = array_values($tagArray)[0];
		$name = $tag->getName();
		if (!empty($input->getOption('name'))) {
			$name = $input->getOption('name');
		}

		$userVisible = $tag->isUserVisible();
		$userAssignable = $tag->isUserAssignable();
		if ($input->getOption('access')) {
			switch ($input->getOption('access')) {
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
		}

		try {
			$this->systemTagManager->updateTag($input->getArgument('id'), $name, $userVisible, $userAssignable);
			$output->writeln('<info>Tag updated ("' . $name . '", '. $userVisible . ', ' . $userAssignable . ')</info>');
			return 0;
		} catch (TagNotFoundException $e) {
			$output->writeln('<error>Tag not found</error>');
			return 1;
		} catch (TagAlreadyExistsException $e) {
			$output->writeln('<error>'.$e->getMessage().'</error>');
			return 2;
		}
	}
}
