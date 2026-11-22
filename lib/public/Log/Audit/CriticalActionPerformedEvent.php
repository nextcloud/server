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

	private ?string $operation;

	/**
	 * @param string $logMessage
	 * @param array $parameters
	 * @param bool $obfuscateParameters
	 * @param ?string $operation Stable identifier of the action in the form `app.entity.action`, e.g. `federatedfilesharing.share.accepted`
	 * @throws \InvalidArgumentException if $operation is not in the form `app.entity.action`
	 * @since 22.0.0
	 * @since 36.0.0 added the $operation parameter
	 */
	public function __construct(string $logMessage,
		array $parameters = [],
		bool $obfuscateParameters = false,
		?string $operation = null) {
		parent::__construct();
		if ($operation !== null && !preg_match('/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/', $operation)) {
			throw new \InvalidArgumentException('Operation "' . $operation . '" is not in the form app.entity.action');
		}
		$this->logMessage = $logMessage;
		$this->parameters = $parameters;
		$this->obfuscateParameters = $obfuscateParameters;
		$this->operation = $operation;
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

	/**
	 * @since 36.0.0
	 */
	public function getOperation(): ?string {
		return $this->operation;
	}
}
