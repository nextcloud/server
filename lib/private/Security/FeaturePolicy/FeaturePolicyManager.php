<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\FeaturePolicy;

use OCP\AppFramework\Http\EmptyFeaturePolicy;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\FeaturePolicy\AddFeaturePolicyEvent;

class FeaturePolicyManager {
	/** @var EmptyFeaturePolicy[] */
	private array $policies = [];

	public function __construct(
		private IEventDispatcher $dispatcher,
	) {
	}

	public function addDefaultPolicy(EmptyFeaturePolicy $policy): void {
		$this->policies[] = $policy;
	}

	public function getDefaultPolicy(): FeaturePolicy {
		$event = new AddFeaturePolicyEvent($this);
		$this->dispatcher->dispatchTyped($event);

		$defaultPolicy = new FeaturePolicy();
		foreach ($this->policies as $policy) {
			$defaultPolicy = $this->mergePolicies($defaultPolicy, $policy);
		}
		return $defaultPolicy;
	}

	/**
	 * Merges the first given policy with the second one
	 *
	 */
	public function mergePolicies(
		FeaturePolicy $defaultPolicy,
		EmptyFeaturePolicy $originalPolicy,
	): FeaturePolicy {
		foreach ((object)(array)$originalPolicy as $name => $value) {
			$setter = 'set' . ucfirst($name);
			if (\is_array($value)) {
				$getter = 'get' . ucfirst($name);
				$currentValues = \is_array($defaultPolicy->$getter()) ? $defaultPolicy->$getter() : [];
				$defaultPolicy->$setter(\array_values(\array_unique(\array_merge($currentValues, $value))));
			} elseif (\is_bool($value)) {
				$defaultPolicy->$setter($value);
			}
		}

		return $defaultPolicy;
	}
}
