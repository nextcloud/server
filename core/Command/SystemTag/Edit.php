<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	public function __construct(
		protected ISystemTagManager $systemTagManager,
	) {
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
			)
			->addOption(
				'color',
				null,
				InputOption::VALUE_OPTIONAL,
				'set the tag color',
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

		$color = $tag->getColor();
		if ($input->hasOption('color')) {
			$color = $input->getOption('color');
			if (substr($color, 0, 1) === '#') {
				$color = substr($color, 1);
			}

			if ($input->getOption('color') === '') {
				$color = null;
			} elseif (strlen($color) !== 6 || !ctype_xdigit($color)) {
				$output->writeln('<error>Color must be a 6-digit hexadecimal value</error>');
				return 2;
			}
		}

		try {
			$this->systemTagManager->updateTag($input->getArgument('id'), $name, $userVisible, $userAssignable, $color);
			$output->writeln('<info>Tag updated ("' . $name . '", ' . json_encode($userVisible) . ', ' . json_encode($userAssignable) . ', "' . ($color ? "#$color" : '') . '")</info>');
			return 0;
		} catch (TagNotFoundException $e) {
			$output->writeln('<error>Tag not found</error>');
			return 1;
		} catch (TagAlreadyExistsException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 2;
		}
	}
}
