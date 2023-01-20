<?php

namespace OCP\Files\AppData;

use OCP\Files\IAppData;

/**
 * A factory allows you to get the AppData folder for an application.
 *
 * @since 25.0.0
 */
interface IAppDataFactory {
	/**
	 * Get the AppData folder for the specified $appId
	 * @param string $appId
	 * @return IAppData
	 * @since 25.0.0
	 */
	public function get(string $appId): IAppData;
}
