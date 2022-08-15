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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Base {
	protected ISystemTagManager $systemTagManager;

	public function __construct(ISystemTagManager $systemTagManager) {
		$this->systemTagManager = $systemTagManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('tag:list')
			->setDescription('list tags')
			->addOption(
				'visibilityFilter',
				null,
				InputOption::VALUE_OPTIONAL,
				'filter by visibility (1,0)'
			)
			->addOption(
				'nameSearchPattern',
				null,
				InputOption::VALUE_OPTIONAL,
				'optional search pattern for the tag name (infix)'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$tags = $this->systemTagManager->getAllTags(
			$input->getOption('visibilityFilter'),
			$input->getOption('nameSearchPattern')
		);

		$this->writeArrayInOutputFormat($input, $output, $this->formatTags($tags));
		return 0;
	}

	/**
	 * @param ISystemtag[] $tags
	 * @return array
	 */
	private function formatTags(array $tags): array {
		$result = [];

		foreach ($tags as $tag) {
			$result[$tag->getId()] = [
				'name' => $tag->getName(),
				'access' => ISystemTag::ACCESS_LEVEL_LOOKUP[$tag->getAccessLevel()],
			];
		}
		return $result;
	}
}
