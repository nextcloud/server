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

	// Users, groups and two-factor providers
	case UserDisplayNameChanged = 'users.user.display_name_changed';
	case UserQuotaChanged = 'users.user.quota_changed';
	case UserManagersChanged = 'users.user.managers_changed';
	case GroupDisplayNameChanged = 'groups.group.display_name_changed';
	case SubAdminAdded = 'groups.subadmin.added';
	case SubAdminRemoved = 'groups.subadmin.removed';
	case TwoFactorProviderEnabled = 'auth.twofactor_provider.enabled';
	case TwoFactorProviderDisabled = 'auth.twofactor_provider.disabled';

	// System tags
	case SystemTagUpdated = 'systemtags.tag.updated';
	case SystemTagDeleted = 'systemtags.tag.deleted';
	case SystemTagGroupsUpdated = 'systemtags.tag_groups.updated';
	case SystemTagAssigned = 'systemtags.tag.assigned';
	case SystemTagUnassigned = 'systemtags.tag.unassigned';

	// Files
	case OwnershipTransferRequested = 'files.ownership_transfer.requested';
	case OwnershipTransferAccepted = 'files.ownership_transfer.accepted';
	case OwnershipTransferRejected = 'files.ownership_transfer.rejected';
	case OwnershipTransferred = 'files.ownership.transferred';

	// Files sharing
	case ShareAccepted = 'sharing.share.accepted';
	case ShareLeft = 'sharing.share.left';
	case ShareRestored = 'sharing.share.restored';
	case ShareAttributesUpdated = 'sharing.share.attributes_updated';
	case ShareTokenUpdated = 'sharing.share.token_updated';
	case RemoteShareAccepted = 'files_sharing.remote_share.accepted';
	case RemoteShareDeclined = 'files_sharing.remote_share.declined';
	case RemoteShareRemoved = 'files_sharing.remote_share.removed';

	// Federated file sharing
	case FederatedShareReceived = 'federatedfilesharing.share.received';
	case FederatedShareAccepted = 'federatedfilesharing.share.accepted';
	case FederatedShareDeclined = 'federatedfilesharing.share.declined';
	case FederatedShareUnshared = 'federatedfilesharing.share.unshared';
	case FederatedShareRevoked = 'federatedfilesharing.share.revoked';
	case FederatedSharePermissionsUpdated = 'federatedfilesharing.share.permissions_updated';

	// Cloud federation API
	case CloudFederationShareReceived = 'cloud_federation_api.share.received';
	case CloudFederationNotificationReceived = 'cloud_federation_api.notification.received';

	// External storage
	case ExternalStorageCreated = 'files_external.storage.created';
	case ExternalStorageUpdated = 'files_external.storage.updated';
	case ExternalStorageDeleted = 'files_external.storage.deleted';
	case ExternalStorageCredentialsUpdated = 'files_external.credentials.updated';
	case ExternalStorageUserCredentialsUpdated = 'files_external.user_credentials.updated';

	// Encryption
	case EncryptionRecoveryKeyEnabled = 'encryption.recovery_key.enabled';
	case EncryptionRecoveryKeyDisabled = 'encryption.recovery_key.disabled';
	case EncryptionRecoveryKeyPasswordChanged = 'encryption.recovery_key.password_changed';
	case EncryptionUserRecoveryUpdated = 'encryption.user_recovery.updated';
	case EncryptionUserFilesRecovered = 'encryption.user_files.recovered';
	case EncryptionUserKeysRegenerated = 'encryption.user_keys.regenerated';
	case EncryptionPrivateKeyPasswordChanged = 'encryption.private_key.password_changed';
	case EncryptionHomeStorageUpdated = 'encryption.home_storage.updated';

	// Federation
	case FederationTrustedServerAdded = 'federation.trusted_server.added';
	case FederationTrustedServerRemoved = 'federation.trusted_server.removed';
	case FederationSharedSecretExchanged = 'federation.shared_secret.exchanged';
}
