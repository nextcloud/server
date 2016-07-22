<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC\Repair;

use OC\Files\View;
use OC\Server;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class UpdateCertificateStore rewrites the user specific certificate store after
 * an update has been performed. This is done because a new root certificate file
 * might have been added.
 *
 * @package OC\Repair
 */
class UpdateCertificateStore implements IRepairStep {
	/**
	 * FIXME: The certificate manager does only allow specifying the user
	 *        within the constructor. This makes DI impossible.
	 * @var Server
	 */
	protected $server;
	/** @var IConfig */
	protected $config;

	/**
	 * @param Server $server
	 * @param IConfig $config
	 */
	public function __construct(Server $server,
								IConfig $config) {
		$this->server = $server;
		$this->config = $config;
	}

	/** {@inheritDoc} */
	public function getName() {
		return 'Update user certificate stores with new root certificates';
	}

	/** {@inheritDoc} */
	public function run(IOutput $out) {
		$rootView = new View();
		$dataDirectory = $this->config->getSystemValue('datadirectory', null);
		if(is_null($dataDirectory)) {
			throw new \Exception('No data directory specified');
		}

		$pathToRootCerts = '/files_external/rootcerts.crt';

		foreach($rootView->getDirectoryContent('', 'httpd/unix-directory') as $fileInfo) {
			$uid = trim($fileInfo->getPath(), '/');
			if($rootView->file_exists($uid . $pathToRootCerts)) {
				// Delete the existing root certificate
				$rootView->unlink($uid . $pathToRootCerts);

				/**
				 * FIXME: The certificate manager does only allow specifying the user
				 *        within the constructor. This makes DI impossible.
				 */
				// Regenerate the certificates
				$certificateManager = $this->server->getCertificateManager($uid);
				$certificateManager->createCertificateBundle();
			}
		}
	}
}
