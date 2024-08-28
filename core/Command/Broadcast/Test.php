<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Broadcast;

use OCP\EventDispatcher\ABroadcastedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Test extends Command {
	public function __construct(
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('broadcast:test')
			->setDescription('test the SSE broadcaster')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'the UID of the users to receive the event'
			)
			->addArgument(
				'name',
				InputArgument::OPTIONAL,
				'the event name',
				'test'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');
		$uid = $input->getArgument('uid');

		$event = new class($name, $uid) extends ABroadcastedEvent {
			/** @var string */
			private $name;
			/** @var string */
			private $uid;

			public function __construct(string $name,
				string $uid) {
				parent::__construct();
				$this->name = $name;
				$this->uid = $uid;
			}

			public function broadcastAs(): string {
				return $this->name;
			}

			public function getUids(): array {
				return [
					$this->uid,
				];
			}

			public function jsonSerialize(): array {
				return [
					'description' => 'this is a test event',
				];
			}
		};

		$this->eventDispatcher->dispatch('broadcasttest', $event);

		return 0;
	}
}
