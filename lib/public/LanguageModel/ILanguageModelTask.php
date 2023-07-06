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

namespace OCP\LanguageModel;

/**
 * @since 28.0.0
 * @template T of ILanguageModelProvider
 */
interface ILanguageModelTask extends \JsonSerializable {
	/**
	 * @since 28.0.0
	 */
	public const STATUS_FAILED = 4;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_SUCCESSFUL = 3;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_RUNNING = 2;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_SCHEDULED = 1;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_UNKNOWN = 0;

	/**
	 * @since 28.0.0
	 */
	public const TYPES = [
		FreePromptTask::TYPE => FreePromptTask::class,
		SummaryTask::TYPE => SummaryTask::class,
		HeadlineTask::TYPE => HeadlineTask::class,
		TopicsTask::TYPE => TopicsTask::class,
	];

	/**
	 * @psalm-param T $provider
	 * @param ILanguageModelProvider $provider
	 * @return string
	 * @since 28.0.0
	 */
	public function visitProvider(ILanguageModelProvider $provider): string;

	/**
	 * @psalm-param T $provider
	 * @param ILanguageModelProvider $provider
	 * @return bool
	 * @since 28.0.0
	 */
	public function canUseProvider(ILanguageModelProvider $provider): bool;


	/**
	 * @return string
	 * @since 28.0.0
	 */
	public function getType(): string;

	/**
	 * @return ILanguageModelTask::STATUS_*
	 * @since 28.0.0
	 */
	public function getStatus(): int;

	/**
	 * @param ILanguageModelTask::STATUS_* $status
	 * @since 28.0.0
	 */
	public function setStatus(int $status): void;

	/**
	 * @param int|null $id
	 * @since 28.0.0
	 */
	public function setId(?int $id): void;

	/**
	 * @return int|null
	 * @since 28.0.0
	 */
	public function getId(): ?int;

	/**
	 * @return string
	 * @since 28.0.0
	 */
	public function getInput(): string;

	/**
	 * @param string|null $output
	 * @since 28.0.0
	 */
	public function setOutput(?string $output): void;

	/**
	 * @return null|string
	 * @since 28.0.0
	 */
	public function getOutput(): ?string;

	/**
	 * @return string
	 * @since 28.0.0
	 */
	public function getAppId(): string;

	/**
	 * @return string|null
	 * @since 28.0.0
	 */
	public function getUserId(): ?string;
}
