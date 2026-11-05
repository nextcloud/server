<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Permission;

use NCU\Permission\ABooleanPermission;
use NCU\Permission\CorePermission;
use OC\Core\AppInfo\Application;
use OCP\L10N\IFactory;

final class UpdatePermission extends ABooleanPermission {
	public function __construct(
		private readonly IFactory $l10nFactory,
	) {
	}

	#[\Override]
	public function getId(): string {
		return CorePermission::Update->value;
	}

	#[\Override]
	public function getDisplayName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('Edit');
	}

	#[\Override]
	public function getPriority(): int {
		return 90;
	}

	#[\Override]
	public function getDefaultValue(): string {
		return self::VALUE_FALSE;
	}

	#[\Override]
	public function getRequiredPermissionIds(): array {
		return [CorePermission::Read->value];
	}
}
