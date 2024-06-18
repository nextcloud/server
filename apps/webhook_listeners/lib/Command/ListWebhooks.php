<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Command;

use OC\Core\Command\Base;
use OCA\WebhookListeners\Db\WebhookListener;
use OCA\WebhookListeners\Db\WebhookListenerMapper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListWebhooks extends Base {
	public function __construct(
		private WebhookListenerMapper $mapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('webhook_listeners:list')
			->setDescription('Lists configured webhook listeners');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$webhookListeners = array_map(
			fn (WebhookListener $listener): array => array_map(
				fn (string|array|null $value): ?string => (is_array($value) ? json_encode($value) : $value),
				$listener->jsonSerialize()
			),
			$this->mapper->getAll()
		);
		$this->writeTableInOutputFormat($input, $output, $webhookListeners);
		return static::SUCCESS;
	}
}
