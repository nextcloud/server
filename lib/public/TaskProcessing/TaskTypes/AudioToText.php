<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Marcel Klehr <mklehr@gmx.net>
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

namespace OCP\TaskProcessing\TaskTypes;

use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\ITaskType;
use OCP\TaskProcessing\ShapeDescriptor;

/**
 * This is the task processing task type for generic transcription
 * @since 30.0.0
 */
class AudioToText implements ITaskType {
	const ID = 'core:audio2text';

	private IL10N $l;

	/**
	 * @param IFactory $l10nFactory
	 * @since 30.0.0
	 */
	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('core');
	}


	/**
	 * @inheritDoc
	 * @since 30.0.0
	 */
	public function getName(): string {
		return $this->l->t('Transcribe audio');
	}

	/**
	 * @inheritDoc
	 * @since 30.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Transcribe the things said in an audio');
	}

	public function getId(): string {
		return self::ID;
	}

	public function getInputShape(): array {
		return [
			'input' => new ShapeDescriptor(
				$this->l->t('Audio input'),
				$this->l->t('The audio to transcribe'),
				EShapeType::Audio
			),
		];
	}

	public function getOutputShape(): array {
		return [
			'output' => new ShapeDescriptor(
				$this->l->t('Transcription'),
				$this->l->t('The transcribed text'),
				EShapeType::Text
			),
		];
	}
}
