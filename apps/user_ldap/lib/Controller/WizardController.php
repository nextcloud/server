<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Controller;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\ConnectionFactory;
use OCA\User_LDAP\Settings\Admin;
use OCA\User_LDAP\WizardFactory;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class WizardController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private LoggerInterface $logger,
		private ConnectionFactory $connectionFactory,
		private IL10N $l,
		private WizardFactory $wizardFactory,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Run a wizard action and returns the result
	 *
	 * @param string $configID ID of the LDAP configuration
	 * @param string $wizardAction Wizard action to run
	 * @param ?string $loginName Login name to test for testLoginName action
	 * @return DataResponse<Http::STATUS_OK, array{changes:array<string,int|string|list<int>|list<string>>,options?:array<string,list<string>>}, array{}>
	 * @throws OCSException
	 *
	 * 200: Wizard action result
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	#[ApiRoute(verb: 'POST', url: '/api/v1/wizard/{configID}/{wizardAction}')]
	public function action(
		string $configID, string $wizardAction,
		?string $loginName = null,
	) {
		try {
			$wizard = $this->wizardFactory->get($configID);
			switch ($wizardAction) {
				case 'guessPortAndTLS':
				case 'guessBaseDN':
				case 'detectEmailAttribute':
				case 'detectUserDisplayNameAttribute':
				case 'determineGroupMemberAssoc':
				case 'determineUserObjectClasses':
				case 'determineGroupObjectClasses':
				case 'determineGroupsForUsers':
				case 'determineGroupsForGroups':
				case 'determineAttributes':
				case 'getUserListFilter':
				case 'getUserLoginFilter':
				case 'getGroupFilter':
				case 'countUsers':
				case 'countGroups':
				case 'countInBaseDN':
					try {
						$result = $wizard->$wizardAction();
						if ($result !== false) {
							return new DataResponse($result->getResultArray());
						}
					} catch (\Exception $e) {
						throw new OCSException($e->getMessage());
					}
					throw new OCSException();

				case 'testLoginName':
					try {
						if ($loginName === null || $loginName === '') {
							throw new OCSException('No login name passed');
						}
						$result = $wizard->$wizardAction($loginName);
						if ($result !== false) {
							return new DataResponse($result->getResultArray());
						}
					} catch (\Exception $e) {
						throw new OCSException($e->getMessage());
					}
					throw new OCSException();

				default:
					throw new OCSException($this->l->t('Action does not exist'));
					break;
			}
		} catch (OCSException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException('An issue occurred when creating the new config.');
		}
	}
}
