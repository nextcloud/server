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
 */


namespace OCP\SpeechToText\Events;

use OCP\Files\File;

/**
 * This Event is emitted if a transcription of a media file using a Speech-To-Text provider failed
 * @since 27.0.0
 */
class TranscriptionFailedEvent extends AbstractTranscriptionEvent {
	/**
	 * @since 27.0.0
	 */
	public function __construct(
		int $fileId,
		?File $file,
		private string $errorMessage,
		?string $userId,
		string $appId,
	) {
		parent::__construct($fileId, $file, $userId, $appId);
	}

	/**
	 * @since 27.0.0
	 * @return string The error message
	 */
	public function getErrorMessage(): string {
		return $this->errorMessage;
	}
}
