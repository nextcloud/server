<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Support;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Helper;
use OCP\IL10N;
use OCP\SystemReport\ISystemReportSection;
use OCP\SystemReport\SystemReportDetail;
use OCP\SystemReport\SystemReportDetailFormat;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Contributes the configured LDAP backends to the system report generated
 * by the support app.
 */
class SystemReportSection implements ISystemReportSection {
	public function __construct(
		private readonly Helper $helper,
		private readonly IL10N $l10n,
	) {
	}

	#[\Override]
	public function getId(): string {
		return 'ldap';
	}

	#[\Override]
	public function getTitle(): string {
		return $this->l10n->t('LDAP');
	}

	#[\Override]
	public function getDetails(): array {
		$configIds = $this->helper->getServerConfigurationPrefixes();
		if ($configIds === []) {
			return [];
		}

		$output = new BufferedOutput();
		foreach ($configIds as $id) {
			$configHolder = new Configuration($id);
			$output->write($this->renderConfiguration($id, $configHolder->getConfiguration()));
		}

		return [
			new SystemReportDetail(
				$this->l10n->t('LDAP configuration'),
				$output->fetch(),
				SystemReportDetailFormat::Preformatted,
			),
		];
	}

	/** @param array<string, mixed> $configuration */
	public function renderConfiguration(string $id, array $configuration): string {
		ksort($configuration);

		$rows = [];
		foreach ($configuration as $key => $value) {
			if ($key === 'ldapAgentPassword') {
				$value = '***';
			} elseif (is_array($value)) {
				$value = implode(';', $value);
			}
			$rows[] = [$key, $value];
		}

		$output = new BufferedOutput();
		$table = new Table($output);
		$table->setHeaders(['Configuration', $id]);
		$table->setRows($rows);
		$table->render();

		return $output->fetch();
	}
}
