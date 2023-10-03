<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Settings\AccountWarnings;

use OCA\Settings\Db\ClientDiagnostic;
use OCA\Settings\Db\ClientDiagnosticMapper;
use OCP\Settings\IAccountWarningsProvider;
use OC\Authentication\Token\IToken;
use OC\Authentication\Token\IProvider;
use OCP\IL10N;
use OCP\IUserManager;

class ClientDiagnosticWarningsProvider implements IAccountWarningsProvider {
	/**
	 * Maximum age for a client diagnostic to be considered still valid, in seconds
	 * Diagnostic timestamp will be compared to last check of the associated authtoken
	 */
	public const MAX_AGE = 24 * 60 * 60;

	public function __construct(
		private IL10N $l10n,
		private IUserManager $userManager,
		private ClientDiagnosticMapper $diagnosticMapper,
		private IProvider $tokenProvider,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Client errors');
	}

	public function getAccountWarnings(): array {
		$diagnostics = $this->diagnosticMapper->getAll();

		$warnings = [];
		foreach ($diagnostics as $diagnostic) {
			$token = $this->getAuthtoken($diagnostic);
			// if (!$this->isRecentEnough($diagnostic, $token)) {
			// 	// TODO delete diagnostic?
			// 	continue;
			// }
			$data = $diagnostic->getDiagnosticAsArray();
			foreach ($data['problems'] as $type => $problemDetails) {
				$warnings[] = new ClientDiagnosticWarning(
					$this->l10n,
					$type,
					$problemDetails['count'],
					$token->getUID(),
					$token->getName(),
					$problemDetails['oldest']
				);
			}
		}
		return $warnings;
	}

	private function getAuthtoken(ClientDiagnostic $diagnostic): IToken {
		return $this->tokenProvider->getTokenById($diagnostic->getAuthtokenid());
	}

	private function isRecentEnough(ClientDiagnostic $diagnostic, IToken $token): bool {
		return ($diagnostic->getTimestamp()->getTimestamp() >= $token->getLastCheck() + self::MAX_AGE);
	}
}
