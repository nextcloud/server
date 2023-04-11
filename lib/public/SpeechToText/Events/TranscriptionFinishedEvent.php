<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
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
namespace OCP\SpeechToText\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 27.0.0
 */
class TranscriptionFinishedEvent extends Event {

	/**
	 * @since 27.0.0
	 */
	public function __construct(
		private bool $successful,
		private string $transcription,
		private string $errorMessage,
		private array $context
	) {
		parent::__construct();
	}

	/**
	 * @since 27.0.0
	 */
	public function getContext(): array {
		return $this->context;
	}

	/**
	 * @since 27.0.0
	 */
	public function isSuccessful(): bool {
		return $this->successful;
	}

	/**
	 * @since 27.0.0
	 */
	public function getErrorMessage(): string {
		return $this->errorMessage;
	}

	/**
	 * @since 27.0.0
	 */
	public function getTranscription(): string {
		return $this->transcription;
	}
}
