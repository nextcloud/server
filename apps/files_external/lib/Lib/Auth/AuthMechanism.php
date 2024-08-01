<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth;

use OCA\Files_External\Lib\FrontendDefinitionTrait;
use OCA\Files_External\Lib\IdentifierTrait;
use OCA\Files_External\Lib\IFrontendDefinition;
use OCA\Files_External\Lib\IIdentifier;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Lib\StorageModifierTrait;
use OCA\Files_External\Lib\VisibilityTrait;

/**
 * Authentication mechanism
 *
 * An authentication mechanism can have services injected during construction,
 * such as \OCP\IDB for database operations. This allows an authentication
 * mechanism to perform advanced operations based on provided information.
 *
 * An authentication scheme defines the parameter interface, common to the
 * storage implementation, the backend and the authentication mechanism.
 * A storage implementation expects parameters according to the authentication
 * scheme, which are provided from the authentication mechanism.
 *
 * This class uses the following traits:
 *  - VisibilityTrait
 *      Restrict usage to admin-only/none
 *  - FrontendDefinitionTrait
 *      Specify configuration parameters and other definitions
 *  - StorageModifierTrait
 *      Object can affect storage mounting
 */
class AuthMechanism implements \JsonSerializable, IIdentifier, IFrontendDefinition {
	/** Standard authentication schemes */
	public const SCHEME_NULL = 'null';
	public const SCHEME_BUILTIN = 'builtin';
	public const SCHEME_PASSWORD = 'password';
	public const SCHEME_OAUTH1 = 'oauth1';
	public const SCHEME_OAUTH2 = 'oauth2';
	public const SCHEME_PUBLICKEY = 'publickey';
	public const SCHEME_OPENSTACK = 'openstack';
	public const SCHEME_SMB = 'smb';

	use VisibilityTrait;
	use FrontendDefinitionTrait;
	use StorageModifierTrait;
	use IdentifierTrait;

	/** @var string */
	protected $scheme;

	/**
	 * Get the authentication scheme implemented
	 * See self::SCHEME_* constants
	 *
	 * @return string
	 */
	public function getScheme() {
		return $this->scheme;
	}

	/**
	 * @param string $scheme
	 * @return $this
	 */
	public function setScheme($scheme) {
		$this->scheme = $scheme;
		return $this;
	}

	/**
	 * Serialize into JSON for client-side JS
	 */
	public function jsonSerialize(): array {
		$data = $this->jsonSerializeDefinition();
		$data += $this->jsonSerializeIdentifier();

		$data['scheme'] = $this->getScheme();
		$data['visibility'] = $this->getVisibility();

		return $data;
	}

	/**
	 * Check if parameters are satisfied in a StorageConfig
	 *
	 * @param StorageConfig $storage
	 * @return bool
	 */
	public function validateStorage(StorageConfig $storage) {
		// does the backend actually support this scheme
		$supportedSchemes = $storage->getBackend()->getAuthSchemes();
		if (!isset($supportedSchemes[$this->getScheme()])) {
			return false;
		}

		return $this->validateStorageDefinition($storage);
	}
}
