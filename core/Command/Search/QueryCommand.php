<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Search;

use NCU\Search\AccountScopedSearchResult;
use NCU\Search\Exceptions\AccountUnavailableException;
use NCU\Search\Exceptions\SearchTruncatedException;
use NCU\Search\IAccountScopedSearchProviderRegistry;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;

#[AsCommand(
	name: 'search:query',
	description: 'Run a query against a registered account-scoped search provider',
	help: 'This command is experimental.',
	usages: [
		'files alice --where name=roadmap',
		'contacts bob --where email=example.com --limit 5',
	],
	supportsOutputFormat: true,
)]
class QueryCommand {
	public function __construct(
		private readonly IAccountScopedSearchProviderRegistry $registry,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(description: 'Id of the provider to query, e.g. "files", "contacts", "calendar"')]
		string $provider,
		#[Argument(description: 'Account to search')]
		string $user,
		#[Option(description: 'A "field=value" condition; repeat for more, they are ANDed together')]
		array $where = [],
		#[Option(description: 'Maximum number of results')]
		int $limit = 20,
		#[Option(description: 'Number of matches to skip')]
		int $offset = 0,
		#[Option(description: 'Also read the detail-only properties, with one extra lookup per result')]
		bool $detail = false,
	): ExitCode {
		$searchProvider = $this->registry->getProvider($provider);
		if ($searchProvider === null) {
			$output->writeln('<error>No such provider "' . $provider . '". Registered: '
				. implode(', ', array_keys($this->registry->getProviders())) . '</error>');

			return ExitCode::Invalid;
		}

		try {
			$filter = $this->buildFilter($where);
		} catch (\InvalidArgumentException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');

			return ExitCode::Invalid;
		}

		$rows = [];
		try {
			foreach ($searchProvider->search($user, $filter, $limit, $offset) as $result) {
				if ($detail) {
					$result = $searchProvider->get($user, $result->getId()) ?? $result;
				}
				$rows[] = $this->formatResult($result);
			}
		} catch (AccountUnavailableException|SearchTruncatedException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');

			return ExitCode::Failure;
		}

		if ($rows === []) {
			$output->writeln('No results.');

			return ExitCode::Success;
		}

		$output->writeTableInOutputFormat($rows);

		return ExitCode::Success;
	}

	/**
	 * @return array<string, string>
	 */
	private function formatResult(AccountScopedSearchResult $result): array {
		$row = ['id' => $result->getId(), 'title' => $result->getTitle()];
		foreach ($result->getMetadata() as $name => $value) {
			$row[$name] = $this->formatValue($value);
		}
		foreach ($result->getMetadataErrors() as $name => $reason) {
			$row[$name] = '(' . $reason . ')';
		}

		return $row;
	}

	/**
	 * @param list<string> $where
	 * @throws \InvalidArgumentException on a malformed condition
	 */
	private function buildFilter(array $where): ?ISearchOperator {
		$conditions = [];
		foreach ($where as $condition) {
			[$field, $value] = array_pad(explode('=', $condition, 2), 2, '');
			if ($field === '') {
				throw new \InvalidArgumentException('Invalid --where "' . $condition . '", expected field=value');
			}
			$conditions[] = new SearchComparison(ISearchComparison::COMPARE_LIKE, $field, '%' . $this->escapeLike($value) . '%');
		}

		return match (count($conditions)) {
			0 => null,
			1 => $conditions[0],
			default => new SearchBinaryOperator(SearchBinaryOperator::OPERATOR_AND, $conditions),
		};
	}

	/**
	 * Escape the LIKE wildcards in a value.
	 */
	private function escapeLike(string $value): string {
		return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
	}

	private function formatValue(mixed $value): string {
		return is_scalar($value) ? (string)$value : (json_encode($value) ?: '');
	}
}
