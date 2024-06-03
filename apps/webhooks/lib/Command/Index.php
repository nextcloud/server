<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Webhooks\Command;

use OC\Core\Command\Base;
use OCA\Webhooks\Db\WebhookListener;
use OCA\Webhooks\Db\WebhookListenerMapper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Index extends Base {
	public function __construct(
		private WebhookListenerMapper $mapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('webhooks:list')
			->setDescription('Lists configured webhooks');
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
