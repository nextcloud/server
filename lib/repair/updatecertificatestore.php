<?php
/**
 * @author Lukas Reschke
 * @copyright 2015 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Repair;

use OC\Files\View;
use OC\Hooks\BasicEmitter;
use OC\RepairStep;
use OC\Server;
use OCP\IConfig;

/**
 * Class UpdateCertificateStore rewrites the user specific certificate store after
 * an update has been performed. This is done because a new root certificate file
 * might have been added.
 *
 * @package OC\Repair
 */
class UpdateCertificateStore extends BasicEmitter implements RepairStep {
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
	public function run() {
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
