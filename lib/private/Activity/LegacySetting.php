<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Activity;

use OCP\Activity\ISetting;

class LegacySetting implements ISetting {

	/** @var string */
	protected $identifier;
	/** @var string */
	protected $name;
	/** @var bool */
	protected $canChangeStream;
	/** @var bool */
	protected $isDefaultEnabledStream;
	/** @var bool */
	protected $canChangeMail;
	/** @var bool */
	protected $isDefaultEnabledMail;

	/**
	 * LegacySetting constructor.
	 *
	 * @param string $identifier
	 * @param string $name
	 * @param bool $canChangeStream
	 * @param bool $isDefaultEnabledStream
	 * @param bool $canChangeMail
	 * @param bool $isDefaultEnabledMail
	 */
	public function __construct($identifier,
								$name,
								$canChangeStream,
								$isDefaultEnabledStream,
								$canChangeMail,
								$isDefaultEnabledMail) {
		$this->identifier = $identifier;
		$this->name = $name;
		$this->canChangeStream = $canChangeStream;
		$this->isDefaultEnabledStream = $isDefaultEnabledStream;
		$this->canChangeMail = $canChangeMail;
		$this->isDefaultEnabledMail = $isDefaultEnabledMail;
	}

	/**
	 * @return string Lowercase a-z and underscore only identifier
	 * @since 11.0.0
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 * the admin section. The filters are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 * @since 11.0.0
	 */
	public function getPriority() {
		return 70;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 * @since 11.0.0
	 */
	public function canChangeStream() {
		return $this->canChangeStream;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 * @since 11.0.0
	 */
	public function isDefaultEnabledStream() {
		return $this->isDefaultEnabledStream;
	}

	/**
	 * @return bool True when the option can be changed for the mail
	 * @since 11.0.0
	 */
	public function canChangeMail() {
		return $this->canChangeMail;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 * @since 11.0.0
	 */
	public function isDefaultEnabledMail() {
		return $this->isDefaultEnabledMail;
	}
}

