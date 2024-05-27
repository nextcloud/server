<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

/**
 * Interface ICallbackResponse
 *
 * @since 8.1.0
 */
interface ICallbackResponse {
	/**
	 * Outputs the content that should be printed
	 *
	 * @param IOutput $output a small wrapper that handles output
	 * @since 8.1.0
	 */
	public function callback(IOutput $output);
}
