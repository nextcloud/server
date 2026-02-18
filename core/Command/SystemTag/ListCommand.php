<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\SystemTag;

use OC\Core\Command\Base;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Base {
	public function __construct(
		protected ISystemTagManager $systemTagManager,
		protected ISystemTagObjectMapper $systemTagObjectMapper,
	) {
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
			)
			->addOption(
				'notUsedByFiles',
				null,
				InputOption::VALUE_OPTIONAL,
				'not used by files (1,0)'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$tags = $this->systemTagManager->getAllTags(
			$input->getOption('visibilityFilter'),
			$input->getOption('nameSearchPattern')
		);

		if ($input->getOption('notUsedByFiles') == 1) {
			$result = [];
			foreach ($tags as $tag) {
				$objId = $this->systemTagObjectMapper->getObjectIdsForTags((string)$tag->getId(), 'files', 1);
				if ($objId == null) {
					$result[] = $tag;
				}
			}
			$tags = $result;
		}

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
