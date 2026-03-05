<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\SystemTag;

use OC\Core\Command\Base;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Base {
	public function __construct(
		protected ISystemTagManager $systemTagManager,
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
