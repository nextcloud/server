<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Controller;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\ConnectionFactory;
use OCA\User_LDAP\Exceptions\ConfigurationIssueException;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Settings\Admin;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Server;
use Psr\Log\LoggerInterface;

class ConfigAPIController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private Helper $ldapHelper,
		private LoggerInterface $logger,
		private ConnectionFactory $connectionFactory,
		private IL10N $l,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Create a new (empty) configuration and return the resulting prefix
	 *
	 * @return DataResponse<Http::STATUS_OK, array{configID: string}, array{}>
	 * @throws OCSException
	 *
	 * 200: Config created successfully
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	#[ApiRoute(verb: 'POST', url: '/api/v1/config')]
	public function create() {
		try {
			$configPrefix = $this->ldapHelper->getNextServerConfigurationPrefix();
			$configHolder = new Configuration($configPrefix);
			$configHolder->ldapConfigurationActive = false;
			$configHolder->saveConfiguration();
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException('An issue occurred when creating the new config.');
		}
		return new DataResponse(['configID' => $configPrefix]);
	}

	/**
	 * Delete a LDAP configuration
	 *
	 * @param string $configID ID of the config
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 * @throws OCSNotFoundException Config not found
	 *
	 * 200: Config deleted successfully
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/config/{configID}')]
	public function delete($configID) {
		try {
			$this->ensureConfigIDExists($configID);
			if (!$this->ldapHelper->deleteServerConfiguration($configID)) {
				throw new OCSException('Could not delete configuration');
			}
		} catch (OCSException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException('An issue occurred when deleting the config.');
		}

		return new DataResponse();
	}

	/**
	 * Modify a configuration
	 *
	 * @param string $configID ID of the config
	 * @param array<string, mixed> $configData New config
	 * @return DataResponse<Http::STATUS_OK, array<string, mixed>, array{}>
	 * @throws OCSException
	 * @throws OCSBadRequestException Modifying config is not possible
	 * @throws OCSNotFoundException Config not found
	 *
	 * 200: Config returned
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/config/{configID}')]
	public function modify($configID, $configData) {
		try {
			$this->ensureConfigIDExists($configID);

			if (!is_array($configData)) {
				throw new OCSBadRequestException('configData is not properly set');
			}

			$configuration = new Configuration($configID);
			$configKeys = $configuration->getConfigTranslationArray();

			foreach ($configKeys as $i => $key) {
				if (isset($configData[$key])) {
					$configuration->$key = $configData[$key];
				}
			}

			$configuration->saveConfiguration();
			$this->connectionFactory->get($configID)->clearCache();
		} catch (OCSException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException('An issue occurred when modifying the config.');
		}

		return $this->show($configID, false);
	}

	/**
	 * Get a configuration
	 *
	 * Output can look like this:
	 * <?xml version="1.0"?>
	 * <ocs>
	 *   <meta>
	 *     <status>ok</status>
	 *     <statuscode>200</statuscode>
	 *     <message>OK</message>
	 *   </meta>
	 *   <data>
	 *     <ldapHost>ldaps://my.ldap.server</ldapHost>
	 *     <ldapPort>7770</ldapPort>
	 *     <ldapBackupHost></ldapBackupHost>
	 *     <ldapBackupPort></ldapBackupPort>
	 *     <ldapBase>ou=small,dc=my,dc=ldap,dc=server</ldapBase>
	 *     <ldapBaseUsers>ou=users,ou=small,dc=my,dc=ldap,dc=server</ldapBaseUsers>
	 *     <ldapBaseGroups>ou=small,dc=my,dc=ldap,dc=server</ldapBaseGroups>
	 *     <ldapAgentName>cn=root,dc=my,dc=ldap,dc=server</ldapAgentName>
	 *     <ldapAgentPassword>clearTextWithShowPassword=1</ldapAgentPassword>
	 *     <ldapTLS>1</ldapTLS>
	 *     <turnOffCertCheck>0</turnOffCertCheck>
	 *     <ldapIgnoreNamingRules/>
	 *     <ldapUserDisplayName>displayname</ldapUserDisplayName>
	 *     <ldapUserDisplayName2>uid</ldapUserDisplayName2>
	 *     <ldapUserFilterObjectclass>inetOrgPerson</ldapUserFilterObjectclass>
	 *     <ldapUserFilterGroups></ldapUserFilterGroups>
	 *     <ldapUserFilter>(&amp;(objectclass=nextcloudUser)(nextcloudEnabled=TRUE))</ldapUserFilter>
	 *     <ldapUserFilterMode>1</ldapUserFilterMode>
	 *     <ldapGroupFilter>(&amp;(|(objectclass=nextcloudGroup)))</ldapGroupFilter>
	 *     <ldapGroupFilterMode>0</ldapGroupFilterMode>
	 *     <ldapGroupFilterObjectclass>nextcloudGroup</ldapGroupFilterObjectclass>
	 *     <ldapGroupFilterGroups></ldapGroupFilterGroups>
	 *     <ldapGroupDisplayName>cn</ldapGroupDisplayName>
	 *     <ldapGroupMemberAssocAttr>memberUid</ldapGroupMemberAssocAttr>
	 *     <ldapLoginFilter>(&amp;(|(objectclass=inetOrgPerson))(uid=%uid))</ldapLoginFilter>
	 *     <ldapLoginFilterMode>0</ldapLoginFilterMode>
	 *     <ldapLoginFilterEmail>0</ldapLoginFilterEmail>
	 *     <ldapLoginFilterUsername>1</ldapLoginFilterUsername>
	 *     <ldapLoginFilterAttributes></ldapLoginFilterAttributes>
	 *     <ldapQuotaAttribute></ldapQuotaAttribute>
	 *     <ldapQuotaDefault></ldapQuotaDefault>
	 *     <ldapEmailAttribute>mail</ldapEmailAttribute>
	 *     <ldapCacheTTL>20</ldapCacheTTL>
	 *     <ldapUuidUserAttribute>auto</ldapUuidUserAttribute>
	 *     <ldapUuidGroupAttribute>auto</ldapUuidGroupAttribute>
	 *     <ldapOverrideMainServer></ldapOverrideMainServer>
	 *     <ldapConfigurationActive>1</ldapConfigurationActive>
	 *     <ldapAttributesForUserSearch>uid;sn;givenname</ldapAttributesForUserSearch>
	 *     <ldapAttributesForGroupSearch></ldapAttributesForGroupSearch>
	 *     <ldapExperiencedAdmin>0</ldapExperiencedAdmin>
	 *     <homeFolderNamingRule></homeFolderNamingRule>
	 *     <hasMemberOfFilterSupport></hasMemberOfFilterSupport>
	 *     <useMemberOfToDetectMembership>1</useMemberOfToDetectMembership>
	 *     <ldapExpertUsernameAttr>uid</ldapExpertUsernameAttr>
	 *     <ldapExpertUUIDUserAttr>uid</ldapExpertUUIDUserAttr>
	 *     <ldapExpertUUIDGroupAttr></ldapExpertUUIDGroupAttr>
	 *     <lastJpegPhotoLookup>0</lastJpegPhotoLookup>
	 *     <ldapNestedGroups>0</ldapNestedGroups>
	 *     <ldapPagingSize>500</ldapPagingSize>
	 *     <turnOnPasswordChange>1</turnOnPasswordChange>
	 *     <ldapDynamicGroupMemberURL></ldapDynamicGroupMemberURL>
	 *   </data>
	 * </ocs>
	 *
	 * @param string $configID ID of the config
	 * @param bool $showPassword Whether to show the password
	 * @return DataResponse<Http::STATUS_OK, array<string, mixed>, array{}>
	 * @throws OCSException
	 * @throws OCSNotFoundException Config not found
	 *
	 * 200: Config returned
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	#[ApiRoute(verb: 'GET', url: '/api/v1/config/{configID}')]
	public function show($configID, $showPassword = false) {
		try {
			$this->ensureConfigIDExists($configID);

			$config = new Configuration($configID);
			$data = $config->getConfiguration();
			if (!$showPassword) {
				$data['ldapAgentPassword'] = '***';
			}
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$value = implode(';', $value);
					$data[$key] = $value;
				}
			}
		} catch (OCSException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException('An issue occurred when modifying the config.');
		}

		return new DataResponse($data);
	}

	/**
	 * Test a configuration
	 *
	 * @param string $configID ID of the LDAP config
	 * @return DataResponse<Http::STATUS_OK, array{success:bool,message:string}, array{}>
	 * @throws OCSException An unexpected error happened
	 * @throws OCSNotFoundException Config not found
	 *
	 * 200: Test was run and results are returned
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	#[ApiRoute(verb: 'POST', url: '/api/v1/config/{configID}/test')]
	public function testConfiguration(string $configID) {
		try {
			$this->ensureConfigIDExists($configID);
			$connection = $this->connectionFactory->get($configID);
			$conf = $connection->getConfiguration();
			if ($conf['ldap_configuration_active'] !== '1') {
				//needs to be true, otherwise it will also fail with an irritating message
				$conf['ldap_configuration_active'] = '1';
			}
			try {
				$connection->setConfiguration($conf, throw: true);
			} catch (ConfigurationIssueException $e) {
				return new DataResponse([
					'success' => false,
					'message' => $this->l->t('Invalid configuration: %s', $e->getHint()),
				]);
			}
			// Configuration is okay
			if (!$connection->bind()) {
				return new DataResponse([
					'success' => false,
					'message' => $this->l->t('Valid configuration, but binding failed. Please check the server settings and credentials.'),
				]);
			}
			/*
			* This shiny if block is an ugly hack to find out whether anonymous
			* bind is possible on AD or not. Because AD happily and constantly
			* replies with success to any anonymous bind request, we need to
			* fire up a broken operation. If AD does not allow anonymous bind,
			* it will end up with LDAP error code 1 which is turned into an
			* exception by the LDAP wrapper. We catch this. Other cases may
			* pass (like e.g. expected syntax error).
			*/
			try {
				$ldapWrapper = Server::get(ILDAPWrapper::class);
				$ldapWrapper->read($connection->getConnectionResource(), '', 'objectClass=*', ['dn']);
			} catch (\Exception $e) {
				if ($e->getCode() === 1) {
					return new DataResponse([
						'success' => false,
						'message' => $this->l->t('Invalid configuration: Anonymous binding is not allowed.'),
					]);
				}
			}
			return new DataResponse([
				'success' => true,
				'message' => $this->l->t('Valid configuration, connection established!'),
			]);
		} catch (OCSException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException('An issue occurred when testing the config.');
		}
	}

	/**
	 * Copy a configuration
	 *
	 * @param string $configID ID of the LDAP config
	 * @return DataResponse<Http::STATUS_OK, array{configID:string}, array{}>
	 * @throws OCSException An unexpected error happened
	 * @throws OCSNotFoundException Config not found
	 *
	 * 200: Config was copied, new configID was returned
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	#[ApiRoute(verb: 'POST', url: '/api/v1/config/{configID}/copy')]
	public function copyConfiguration(string $configID) {
		try {
			$this->ensureConfigIDExists($configID);
			$configPrefix = $this->ldapHelper->getNextServerConfigurationPrefix();
			$newConfig = new Configuration($configPrefix, false);
			$originalConfig = new Configuration($configID);
			$newConfig->setConfiguration($originalConfig->getConfiguration());
			$newConfig->saveConfiguration();
			return new DataResponse(['configID' => $configPrefix]);
		} catch (OCSException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException('An issue occurred when creating the new config.');
		}
	}

	/**
	 * If the given config ID is not available, an exception is thrown
	 *
	 * @param string $configID
	 * @throws OCSNotFoundException
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	private function ensureConfigIDExists($configID): void {
		$prefixes = $this->ldapHelper->getServerConfigurationPrefixes();
		if (!in_array($configID, $prefixes, true)) {
			throw new OCSNotFoundException('Config ID not found');
		}
	}
}
