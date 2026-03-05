<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\FeaturePolicy;

class FeaturePolicy extends \OCP\AppFramework\Http\FeaturePolicy {
	public function getAutoplayDomains(): array {
		return $this->autoplayDomains;
	}

	public function setAutoplayDomains(array $autoplayDomains): void {
		$this->autoplayDomains = $autoplayDomains;
	}

	public function getCameraDomains(): array {
		return $this->cameraDomains;
	}

	public function setCameraDomains(array $cameraDomains): void {
		$this->cameraDomains = $cameraDomains;
	}

	public function getFullscreenDomains(): array {
		return $this->fullscreenDomains;
	}

	public function setFullscreenDomains(array $fullscreenDomains): void {
		$this->fullscreenDomains = $fullscreenDomains;
	}

	public function getGeolocationDomains(): array {
		return $this->geolocationDomains;
	}

	public function setGeolocationDomains(array $geolocationDomains): void {
		$this->geolocationDomains = $geolocationDomains;
	}

	public function getMicrophoneDomains(): array {
		return $this->microphoneDomains;
	}

	public function setMicrophoneDomains(array $microphoneDomains): void {
		$this->microphoneDomains = $microphoneDomains;
	}

	public function getPaymentDomains(): array {
		return $this->paymentDomains;
	}

	public function setPaymentDomains(array $paymentDomains): void {
		$this->paymentDomains = $paymentDomains;
	}
}
