<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\FederatedFileSharing\AppInfo;

use OCA\FederatedFileSharing\Listeners\LoadAdditionalScriptsListener;
use OCA\FederatedFileSharing\Notifier;
use OCA\FederatedFileSharing\OCM\CloudFederationProviderFiles;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public function __construct() {
		parent::__construct('federatedfilesharing');
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScriptsListener::class);
	}

	public function boot(IBootContext $context): void {
		$server = $context->getServerContainer();

		$cloudFederationManager = $server->getCloudFederationProviderManager();
		$cloudFederationManager->addCloudFederationProvider('file',
			'Federated Files Sharing',
			function () use ($server) {
				return $server->query(CloudFederationProviderFiles::class);
			});

		$manager = $server->getNotificationManager();
		$manager->registerNotifierService(Notifier::class);
	}
}
