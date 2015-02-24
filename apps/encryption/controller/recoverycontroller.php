<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 2/19/15, 11:25 AM
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Encryption\Controller;


use OCA\Encryption\Recovery;
use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\JSON;
use Symfony\Component\HttpFoundation\JsonResponse;

class RecoveryController extends Controller {
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IL10N
	 */
	private $l;
	/**
	 * @var Recovery
	 */
	private $recovery;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param IL10N $l10n
	 * @param Recovery $recovery
	 */
	public function __construct($AppName, IRequest $request, IConfig $config, IL10N $l10n, Recovery $recovery) {
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->l = $l10n;
		$this->recovery = $recovery;
	}

	public function adminRecovery($recoveryPassword, $confirmPassword, $adminEnableRecovery) {
		// Check if both passwords are the same
		if (empty($recoveryPassword)) {
			$errorMessage = $this->l->t('Missing recovery key password');
			return new JsonResponse(['data' => ['message' => $errorMessage]], 500);
		}

		if (empty($confirmPassword)) {
			$errorMessage = $this->l->t('Please repeat the recovery key password');
			return new JsonResponse(['data' => ['message' => $errorMessage]], 500);
		}

		if ($recoveryPassword !== $confirmPassword) {
			$errorMessage = $this->l->t('Repeated recovery key password does not match the provided recovery key password');
			return new JsonResponse(['data' => ['message' => $errorMessage]], 500);
		}

		// Enable recoveryAdmin
		$recoveryKeyId = $this->config->getAppValue('encryption', 'recoveryKeyId');

		if (isset($adminEnableRecovery) && $adminEnableRecovery === '1') {
			if ($this->recovery->enableAdminRecovery($recoveryKeyId, $recoveryPassword)) {
				return new JsonResponse(['data' => array('message' => $this->l->t('Recovery key successfully enabled'))]);
			}
			return new JsonResponse(['data' => array('message' => $this->l->t('Could not enable recovery key. Please check your recovery key password!'))]);
		} elseif (isset($adminEnableRecovery) && $adminEnableRecovery === '0') {
			if ($this->recovery->disableAdminRecovery($recoveryKeyId, $recoveryPassword)) {
				return new JsonResponse(['data' => array('message' => $this->l->t('Recovery key successfully disabled'))]);
			}
			return new JsonResponse(['data' => array('message' => $this->l->t('Could not disable recovery key. Please check your recovery key password!'))]);
		}
	}

	public function userRecovery($userEnableRecovery) {
		if (isset($userEnableRecovery) && ($userEnableRecovery === '0' || $userEnableRecovery === '1')) {
			$userId = $this->user->getUID();
			if ($userEnableRecovery === '1') {
				// Todo xxx figure out if we need keyid's here or what.
				return $this->recovery->addRecoveryKeys();
			}
			// Todo xxx see :98
			return $this->recovery->removeRecoveryKeys();
		}
	}

}
