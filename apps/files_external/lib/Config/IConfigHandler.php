<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Config;

/**
 * Interface IConfigHandler
 *
 * @package OCA\Files_External\Config
 * @since 16.0.0
 */
interface IConfigHandler {
	/**
	 * @param mixed $optionValue
	 * @return mixed the same type as $optionValue
	 * @since 16.0.0
	 */
	public function handle($optionValue);
}
