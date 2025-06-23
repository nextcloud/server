<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	public function __construct(
		protected ISystemTagManager $systemTagManager,
	) {
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
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 2;
		}
	}
}
