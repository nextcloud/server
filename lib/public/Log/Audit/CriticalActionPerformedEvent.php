<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
