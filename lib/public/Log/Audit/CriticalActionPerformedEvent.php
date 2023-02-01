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
namespace OCP\Log\Audit;

use OCP\EventDispatcher\Event;

/**
 * Emitted when the admin_audit app should log an entry
 *
 * @since 22.0.0
 */
class CriticalActionPerformedEvent extends Event {
	/** @var string */
	private $logMessage;

	/** @var array */
	private $parameters;

	/** @var bool */
	private $obfuscateParameters;

	/**
	 * @param string $logMessage
	 * @param array $parameters
	 * @param bool $obfuscateParameters
	 * @since 22.0.0
	 */
	public function __construct(string $logMessage,
								array $parameters = [],
								bool $obfuscateParameters = false) {
		parent::__construct();
		$this->logMessage = $logMessage;
		$this->parameters = $parameters;
		$this->obfuscateParameters = $obfuscateParameters;
	}

	/**
	 * @return string
	 * @since 22.0.0
	 */
	public function getLogMessage(): string {
		return $this->logMessage;
	}

	/**
	 * @return array
	 * @since 22.0.0
	 */
	public function getParameters(): array {
		return $this->parameters;
	}

	/**
	 * @return bool
	 * @since 22.0.0
	 */
	public function getObfuscateParameters(): bool {
		return $this->obfuscateParameters;
	}
}
