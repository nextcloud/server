<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Copyright (c) 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Two-factor backup codes
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Settings\Activity;

use OCP\Activity\ISetting;
use OCP\IL10N;

class SecuritySetting implements ISetting {

	/** @var IL10N */
	private $l10n;

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	public function canChangeMail() {
		return false;
	}

	public function canChangeStream() {
		return false;
	}

	public function getIdentifier() {
		return 'security';
	}

	public function getName() {
		return $this->l10n->t('Security');
	}

	public function getPriority() {
		return 30;
	}

	public function isDefaultEnabledMail() {
		return true;
	}

	public function isDefaultEnabledStream() {
		return true;
	}

}
