<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Search;

use NCU\Search\IAccountScopedSearchProviderRegistry;
use NCU\Search\MetadataFieldStatus;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchQuery;
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
	help: 'This command is experimental: it queries NCU\Search\IAccountScopedSearchProvider, which is itself experimental and may still change or be removed.',
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
	): ExitCode {
		$searchProvider = $this->registry->getProvider($provider);
		if ($searchProvider === null) {
			$output->writeln('<error>No such provider "' . $provider . '". Registered: '
				. implode(', ', array_keys($this->registry->getProviders())) . '</error>');

			return ExitCode::Invalid;
		}

		$operation = $this->buildOperation($where, $output);
		if ($operation === null) {
			return ExitCode::Invalid;
		}

		$query = new SearchQuery($operation, $limit, $offset, []);

		$rows = [];
		foreach ($searchProvider->search($user, $query) as $result) {
			$row = ['id' => $result->getId(), 'title' => $result->getTitle()];
			foreach ($result->getMetaData() as $field) {
				$row[$field->getName()] = $field->getStatus() === MetadataFieldStatus::Captured
					? $this->formatValue($field->getValue())
					: '(' . $field->getStatus()->value . ')';
			}
			$rows[] = $row;
		}

		if ($rows === []) {
			$output->writeln('No results.');

			return ExitCode::Success;
		}

		$output->writeTableInOutputFormat($rows);

		return ExitCode::Success;
	}

	/**
	 * @param list<string> $where
	 */
	private function buildOperation(array $where, IOutput $output): ?ISearchOperator {
		$conditions = [];
		foreach ($where as $condition) {
			[$field, $value] = array_pad(explode('=', $condition, 2), 2, '');
			if ($field === '') {
				$output->writeln('<error>Invalid --where "' . $condition . '", expected field=value</error>');

				return null;
			}
			$conditions[] = new SearchComparison(ISearchComparison::COMPARE_LIKE, $field, '%' . $this->escapeLike($value) . '%');
		}

		return match (count($conditions)) {
			0 => new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', '%'),
			1 => $conditions[0],
			default => new SearchBinaryOperator(SearchBinaryOperator::OPERATOR_AND, $conditions),
		};
	}

	/**
	 * The wildcards are ours to add, so a value containing one must not act as one.
	 */
	private function escapeLike(string $value): string {
		return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
	}

	private function formatValue(mixed $value): string {
		return is_scalar($value) ? (string)$value : (json_encode($value) ?: '');
	}
}
