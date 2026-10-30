<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Federation;

use OCP\Federation\ISenderDomainAwareCloudFederationNotification;

class SenderDomainAwareCloudFederationNotification extends CloudFederationNotification implements ISenderDomainAwareCloudFederationNotification {
	public function __construct(
		private readonly ?string $senderDomain,
		private readonly ?string $shareType,
	) {
	}

	#[\Override]
	public function getNotificationType(): string {
		return (string)($this->getMessage()['notificationType'] ?? '');
	}

	#[\Override]
	public function getSenderDomain(): ?string {
		return $this->senderDomain;
	}

	#[\Override]
	public function getResourceType(): ?string {
		return $this->getMessage()['resourceType'] ?? null;
	}

	#[\Override]
	public function getShareType(): ?string {
		return $this->shareType;
	}

	#[\Override]
	public function getProviderId(): ?string {
		$providerId = $this->getMessage()['providerId'] ?? '';
		return $providerId === '' ? null : $providerId;
	}

	#[\Override]
	public function getNotification(): array {
		return $this->getMessage()['notification'] ?? [];
	}
}
