<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Search;

use NCU\Search\IAccountScopedSearchProviderRegistry;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

#[AsCommand(
	name: 'search:providers',
	description: 'List the registered account-scoped search providers and the properties each one exposes',
	help: 'This command is experimental.',
	supportsOutputFormat: true,
)]
class ProvidersCommand {
	public function __construct(
		private readonly IAccountScopedSearchProviderRegistry $registry,
	) {
	}

	public function __invoke(IOutput $output): ExitCode {
		$providers = $this->registry->getProviders();
		if ($providers === []) {
			$output->writeln('No account-scoped search providers are registered.');

			return ExitCode::Success;
		}

		$tree = [];
		foreach ($providers as $id => $provider) {
			$fields = [];
			foreach ($provider->getProperties() as $property) {
				$flags = array_keys(array_filter([
					'searchable' => $property->isSearchable(),
					'selectable' => $property->isSelectable(),
					'detail-only' => $property->isDetailOnly(),
					'indexed' => $property->isIndexed(),
				]));
				$fields[] = $property->getName() . ' — ' . $property->getTitle()
					. ' [' . implode(', ', [$property->getType()->value, ...$flags]) . ']';
			}
			$tree[$id . ' (' . $provider->getName() . ')'] = $fields;
		}

		$output->writeTree($tree, 'Account-scoped search providers');

		return ExitCode::Success;
	}
}
