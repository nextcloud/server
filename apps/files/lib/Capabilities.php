<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tom Needham <tom@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files;

use OC\DirectEditing\Manager;
use OCP\Capabilities\ICapability;
use OCP\DirectEditing\ACreateEmpty;
use OCP\DirectEditing\ACreateFromTemplate;
use OCP\DirectEditing\IEditor;
use OCP\DirectEditing\RegisterDirectEditorEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;

/**
 * Class Capabilities
 *
 * @package OCA\Files
 */
class Capabilities implements ICapability {

	/** @var IConfig */
	protected $config;

	/** @var Manager */
	protected $directEditingManager;

	/** @var IEventDispatcher */
	protected $eventDispatcher;

	/**
	 * Capabilities constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config, Manager $manager, IEventDispatcher $eventDispatcher) {
		$this->config = $config;
		$this->directEditingManager = $manager;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array
	 */
	public function getCapabilities() {
		return [
			'files' => [
				'bigfilechunking' => true,
				'blacklisted_files' => $this->config->getSystemValue('blacklisted_files', ['.htaccess']),
				'directEditing' => $this->getDirectEditingCapabilitites()
			],
		];
	}

	private function getDirectEditingCapabilitites(): array {
		$this->eventDispatcher->dispatchTyped(new RegisterDirectEditorEvent($this->directEditingManager));

		$capabilities = [
			'editors' => [],
			'creators' => []
		];

		/**
		 * @var string $id
		 * @var IEditor $editor
		 */
		foreach ($this->directEditingManager->getEditors() as $id => $editor) {
			$capabilities['editors'][$id] = [
				'name' => $editor->getName(),
				'mimetypes' => $editor->getMimetypes(),
				'optionalMimetypes' => $editor->getMimetypesOptional(),
				'secure' => $editor->isSecure(),
			];
			/** @var ACreateEmpty|ACreateFromTemplate $creator */
			foreach ($editor->getCreators() as $creator) {
				$id = $creator->getId();
				$capabilities['creators'][$id] = [
					'id' => $id,
					'name' => $creator->getName(),
					'extension' => $creator->getExtension(),
					'templates' => $creator instanceof ACreateFromTemplate,
					'mimetype' => $creator->getMimetype()
				];
			}
		}
		return $capabilities;
	}
}
