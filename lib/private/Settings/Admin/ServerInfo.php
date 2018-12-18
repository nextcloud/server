<?php

namespace OC\Settings\Admin;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

/**
 * Class ServerInfo
 *
 * @package OC\Settings\Admin
 */
class ServerInfo implements ISettings {

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$parameters = [];
		return new TemplateResponse('settings', 'settings/admin/server-info', $parameters, '');
	}

	/**
	 * Returns the server info section id.
	 *
	 * @return string
	 */
	public function getSection() {
		return 'server-info';
	}

	/**
	 * Returns the server info settings priority.
	 *
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 */
	public function getPriority() {
		return 20;
	}

}
