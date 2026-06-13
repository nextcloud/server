<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TaskProcessing;

/**
 * This is a task type interface that is implemented by task processing
 * task types
 * @since 30.0.0
 */
interface ITaskType {
	/**
	 * Returns the unique id of this task type
	 *
	 * @since 30.0.0
	 * @return string
	 */
	public function getId(): string;

	/**
	 * Returns the localized name of this task type
	 *
	 * @since 30.0.0
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Returns the localized description of this task type
	 *
	 * @since 30.0.0
	 * @return string
	 */
	public function getDescription(): string;

	/**
	 * Returns the shape of the input array
	 *
	 * @since 30.0.0
	 * @psalm-return ShapeDescriptor[]
	 */
	public function getInputShape(): array;

	/**
	 * Returns the shape of the output array
	 *
	 * @since 30.0.0
	 * @psalm-return ShapeDescriptor[]
	 */
	public function getOutputShape(): array;
}
