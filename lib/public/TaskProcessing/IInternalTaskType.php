<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TaskProcessing;

/**
 * This is a task type interface that is implemented by task processing
 * task types that should not show up in the assistant UI
 * @since 33.0.0
 */
interface IInternalTaskType extends ITaskType {

}
