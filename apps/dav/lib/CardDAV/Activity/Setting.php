<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\DAV\CardDAV\Activity;

use OCA\DAV\CalDAV\Activity\Setting\CalDAVSetting;

class Setting extends CalDAVSetting {
	/**
	 * @return string Lowercase a-z and underscore only identifier
	 */
	public function getIdentifier(): string {
		return 'contacts';
	}

	/**
	 * @return string A translated string
	 */
	public function getName(): string {
		return $this->l->t('A <strong>contact</strong> or <strong>address book</strong> was modified');
	}

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 * the admin section. The filters are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 */
	public function getPriority(): int {
		return 50;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 */
	public function canChangeStream(): bool {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 */
	public function isDefaultEnabledStream(): bool {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the mail
	 */
	public function canChangeMail(): bool {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 */
	public function isDefaultEnabledMail(): bool {
		return false;
	}
}
