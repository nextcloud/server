<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
namespace OC\Share20;

use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Notifications;
use OCA\FederatedFileSharing\TokenHandler;
use OCP\Share\IProviderFactory;
use OC\Share20\Exception\ProviderException;
use OCP\IServerContainer;

/**
 * Class ProviderFactory
 *
 * @package OC\Share20
 */
class ProviderFactory implements IProviderFactory {

	/** @var IServerContainer */
	private $serverContainer;
	/** @var DefaultShareProvider */
	private $defaultProvider = null;
	/** @var FederatedShareProvider */
	private $federatedProvider = null;

	/**
	 * IProviderFactory constructor.
	 * @param IServerContainer $serverContainer
	 */
	public function __construct(IServerContainer $serverContainer) {
		$this->serverContainer = $serverContainer;
	}

	/**
	 * Create the default share provider.
	 *
	 * @return DefaultShareProvider
	 */
	protected function defaultShareProvider() {
		if ($this->defaultProvider === null) {
			$this->defaultProvider = new DefaultShareProvider(
				$this->serverContainer->getDatabaseConnection(),
				$this->serverContainer->getUserManager(),
				$this->serverContainer->getGroupManager(),
				$this->serverContainer->getRootFolder()
			);
		}

		return $this->defaultProvider;
	}

	/**
	 * Create the federated share provider
	 *
	 * @return FederatedShareProvider
	 */
	protected function federatedShareProvider() {
		if ($this->federatedProvider === null) {
			/*
			 * TODO: add factory to federated sharing app
			 */
			$l = $this->serverContainer->getL10N('federatedfilessharing');
			$addressHandler = new AddressHandler(
				$this->serverContainer->getURLGenerator(),
				$l
			);
			$notifications = new Notifications(
				$addressHandler,
				$this->serverContainer->getHTTPClientService()
			);
			$tokenHandler = new TokenHandler(
				$this->serverContainer->getSecureRandom()
			);

			$this->federatedProvider = new FederatedShareProvider(
				$this->serverContainer->getDatabaseConnection(),
				$addressHandler,
				$notifications,
				$tokenHandler,
				$l,
				$this->serverContainer->getLogger(),
				$this->serverContainer->getRootFolder()
			);
		}

		return $this->federatedProvider;
	}

	/**
	 * @inheritdoc
	 */
	public function getProvider($id) {
		if ($id === 'ocinternal') {
			return $this->defaultShareProvider();
		}

		if ($id === 'ocFederatedSharing') {
			return $this->federatedShareProvider();
		}

		throw new ProviderException('No provider with id .' . $id . ' found.');
	}

	/**
	 * @inheritdoc
	 */
	public function getProviderForType($shareType) {
		//FIXME we should not report type 2
		if ($shareType === \OCP\Share::SHARE_TYPE_USER  ||
			$shareType === 2 ||
			$shareType === \OCP\Share::SHARE_TYPE_GROUP ||
			$shareType === \OCP\Share::SHARE_TYPE_LINK) {
			return $this->defaultShareProvider();
		} else if ($shareType === \OCP\Share::SHARE_TYPE_REMOTE) {
			return $this->federatedShareProvider();
		}

		throw new ProviderException('No share provider for share type ' . $shareType);
	}
}
