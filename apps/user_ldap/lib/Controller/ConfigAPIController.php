<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Controller;

use OC\CapabilitiesManager;
use OC\Core\Controller\OCSController;
use OC\Security\IdentityProof\Manager;
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\ConnectionFactory;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Settings\Admin;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\ServerVersion;
use Psr\Log\LoggerInterface;

class ConfigAPIController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		CapabilitiesManager $capabilitiesManager,
		IUserSession $userSession,
		IUserManager $userManager,
		Manager $keyManager,
		ServerVersion $serverVersion,
		private Helper $ldapHelper,
		private LoggerInterface $logger,
		private ConnectionFactory $connectionFactory,
	) {
		parent::__construct(
			$appName,
			$request,
			$capabilitiesManager,
			$userSession,
			$userManager,
			$keyManager,
			$serverVersion,
		);
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
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 * @throws OCSBadRequestException Modifying config is not possible
	 * @throws OCSNotFoundException Config not found
	 *
	 * 200: Config returned
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
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

		return new DataResponse();
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
