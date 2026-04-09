<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Group\Backend;

/**
 * @since 22.0.0
 */
interface INamedBackend {
	/**
	 * Backend name to be shown in group management
	 * @return string the name of the backend to be shown
	 * @since 22.0.0
	 */
	public function getBackendName(): string;
}
