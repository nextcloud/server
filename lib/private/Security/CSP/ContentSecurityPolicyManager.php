<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Security\CSP;

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\Security\IContentSecurityPolicyManager;

class ContentSecurityPolicyManager implements IContentSecurityPolicyManager {
	/** @var ContentSecurityPolicy[] */
	private $policies = [];

	/** {@inheritdoc} */
	public function addDefaultPolicy(EmptyContentSecurityPolicy $policy) {
		$this->policies[] = $policy;
	}

	/**
	 * Get the configured default policy. This is not in the public namespace
	 * as it is only supposed to be used by core itself.
	 *
	 * @return ContentSecurityPolicy
	 */
	public function getDefaultPolicy() {
		$defaultPolicy = new \OC\Security\CSP\ContentSecurityPolicy();
		foreach($this->policies as $policy) {
			$defaultPolicy = $this->mergePolicies($defaultPolicy, $policy);
		}
		return $defaultPolicy;
	}

	/**
	 * Merges the first given policy with the second one
	 *
	 * @param ContentSecurityPolicy $defaultPolicy
	 * @param EmptyContentSecurityPolicy $originalPolicy
	 * @return ContentSecurityPolicy
	 */
	public function mergePolicies(ContentSecurityPolicy $defaultPolicy,
								  EmptyContentSecurityPolicy $originalPolicy) {
		foreach((object)(array)$originalPolicy as $name => $value) {
			$setter = 'set'.ucfirst($name);
			if(is_array($value)) {
				$getter = 'get'.ucfirst($name);
				$currentValues = is_array($defaultPolicy->$getter()) ? $defaultPolicy->$getter() : [];
				$defaultPolicy->$setter(array_values(array_unique(array_merge($currentValues, $value))));
			} elseif (is_bool($value)) {
				$defaultPolicy->$setter($value);
			}
		}

		return $defaultPolicy;
	}
}
