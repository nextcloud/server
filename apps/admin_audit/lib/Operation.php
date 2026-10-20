<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit;

/**
 * Stable identifiers of the audited actions, written to the `operation` field of each audit log entry.
 *
 * The values are matched by log consumers (filters, alert rules, retention policies),
 * so changing one is a breaking change for them.
 */
enum Operation: string {
	// Files
	case FileRead = 'files.file.read';
	case FileCreated = 'files.file.created';
	case FileWritten = 'files.file.written';
	case FileRenamed = 'files.file.renamed';
	case FileCopied = 'files.file.copied';
	case FileDeleted = 'files.file.deleted';
	case PreviewAccessed = 'files.preview.accessed';
	case CacheEntryInserted = 'files.cache_entry.inserted';
	case CacheEntryRemoved = 'files.cache_entry.removed';

	// Sharing
	case ShareCreated = 'sharing.share.created';
	case ShareDeleted = 'sharing.share.deleted';
	case SharePermissionsUpdated = 'sharing.share.permissions_updated';
	case SharePasswordUpdated = 'sharing.share.password_updated';
	case ShareExpirationUpdated = 'sharing.share.expiration_updated';
	case ShareExpirationRemoved = 'sharing.share.expiration_removed';
	case ShareLinkAccessed = 'sharing.share.link_accessed';

	// Trash bin and versions
	case TrashbinFileDeleted = 'trashbin.file.deleted';
	case TrashbinFileRestored = 'trashbin.file.restored';
	case VersionDeleted = 'versions.version.deleted';
	case VersionRestored = 'versions.version.restored';

	// Authentication
	case LoginAttempted = 'auth.login.attempted';
	case LoginSucceeded = 'auth.login.succeeded';
	case LoginFailed = 'auth.login.failed';
	case LogoutPerformed = 'auth.logout.performed';
	case TwoFactorPassed = 'auth.twofactor.passed';
	case TwoFactorFailed = 'auth.twofactor.failed';

	// Users
	case UserCreated = 'users.user.created';
	case UserDeleted = 'users.user.deleted';
	case UserEnabled = 'users.user.enabled';
	case UserDisabled = 'users.user.disabled';
	case UserEmailChanged = 'users.user.email_changed';
	case UserPasswordChanged = 'users.user.password_changed';
	case UserIdAssigned = 'users.user.id_assigned';
	case UserIdUnassigned = 'users.user.id_unassigned';

	// Groups
	case GroupCreated = 'groups.group.created';
	case GroupDeleted = 'groups.group.deleted';
	case GroupMemberAdded = 'groups.member.added';
	case GroupMemberRemoved = 'groups.member.removed';

	// Apps, console and system tags
	case AppEnabled = 'apps.app.enabled';
	case AppDisabled = 'apps.app.disabled';
	case AppUpdated = 'apps.app.updated';
	case ConsoleCommandExecuted = 'console.command.executed';
	case SystemTagCreated = 'systemtags.tag.created';
}
