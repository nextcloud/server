<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Security\CSP;

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Security\IContentSecurityPolicyManager;

class ContentSecurityPolicyManager implements IContentSecurityPolicyManager {
	/** @var ContentSecurityPolicy[] */
	private array $policies = [];

	public function __construct(
		private IEventDispatcher $dispatcher,
	) {
	}

	/** {@inheritdoc} */
	public function addDefaultPolicy(EmptyContentSecurityPolicy $policy): void {
		$this->policies[] = $policy;
	}

	/**
	 * Get the configured default policy. This is not in the public namespace
	 * as it is only supposed to be used by core itself.
	 */
	public function getDefaultPolicy(): ContentSecurityPolicy {
		$event = new AddContentSecurityPolicyEvent($this);
		$this->dispatcher->dispatchTyped($event);

		$defaultPolicy = new \OC\Security\CSP\ContentSecurityPolicy();
		foreach ($this->policies as $policy) {
			$defaultPolicy = $this->mergePolicies($defaultPolicy, $policy);
		}
		return $defaultPolicy;
	}

	/**
	 * Merges the first given policy with the second one
	 */
	public function mergePolicies(
		ContentSecurityPolicy $defaultPolicy,
		EmptyContentSecurityPolicy $originalPolicy,
	): ContentSecurityPolicy {
		foreach ((object)(array)$originalPolicy as $name => $value) {
			$setter = 'set' . ucfirst($name);
			if (\is_array($value)) {
				$getter = 'get' . ucfirst($name);
				$currentValues = \is_array($defaultPolicy->$getter()) ? $defaultPolicy->$getter() : [];
				$defaultPolicy->$setter(array_values(array_unique(array_merge($currentValues, $value))));
			} elseif (\is_bool($value)) {
				$getter = 'is' . ucfirst($name);
				$currentValue = $defaultPolicy->$getter();
				// true wins over false
				if ($value > $currentValue) {
					$defaultPolicy->$setter($value);
				}
			}
		}

		return $defaultPolicy;
	}
}
