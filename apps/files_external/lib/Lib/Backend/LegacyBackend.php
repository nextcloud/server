<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Backend;

use OCA\Files_External\Lib\Auth\Builtin;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\LegacyDependencyCheckPolyfill;
use OCA\Files_External\Lib\MissingDependency;

/**
 * Legacy compatibility for OCA\Files_External\MountConfig::registerBackend()
 */
class LegacyBackend extends Backend {
	use LegacyDependencyCheckPolyfill {
		LegacyDependencyCheckPolyfill::checkDependencies as doCheckDependencies;
	}

	/** @var bool */
	protected $hasDependencies = false;

	/**
	 * @param string $class
	 * @param array $definition
	 * @param Builtin $authMechanism
	 */
	public function __construct($class, array $definition, Builtin $authMechanism) {
		$this
			->setIdentifier($class)
			->setStorageClass($class)
			->setText($definition['backend'])
			->addAuthScheme(Builtin::SCHEME_BUILTIN)
			->setLegacyAuthMechanism($authMechanism)
		;

		foreach ($definition['configuration'] as $name => $placeholder) {
			$flags = DefinitionParameter::FLAG_NONE;
			$type = DefinitionParameter::VALUE_TEXT;
			if ($placeholder[0] === '&') {
				$flags = DefinitionParameter::FLAG_OPTIONAL;
				$placeholder = substr($placeholder, 1);
			}
			switch ($placeholder[0]) {
				case '!':
					$type = DefinitionParameter::VALUE_BOOLEAN;
					$placeholder = substr($placeholder, 1);
					break;
				case '*':
					$type = DefinitionParameter::VALUE_PASSWORD;
					$placeholder = substr($placeholder, 1);
					break;
				case '#':
					$type = DefinitionParameter::VALUE_HIDDEN;
					$placeholder = substr($placeholder, 1);
					break;
			}
			$this->addParameter((new DefinitionParameter($name, $placeholder))
				->setType($type)
				->setFlags($flags)
			);
		}

		if (isset($definition['priority'])) {
			$this->setPriority($definition['priority']);
		}
		if (isset($definition['custom'])) {
			$this->addCustomJs($definition['custom']);
		}
		if (isset($definition['has_dependencies']) && $definition['has_dependencies']) {
			$this->hasDependencies = true;
		}
	}

	/**
	 * @return MissingDependency[]
	 */
	public function checkDependencies() {
		if ($this->hasDependencies) {
			return $this->doCheckDependencies();
		}
		return [];
	}
}
