<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Token;

/**
 * Which index.php and OCS routes a token limited to scopes may call, by route name
 */
class TokenRoutes {
	private const INFRASTRUCTURE = [
		'ocs.core.ocs.getcapabilities',
		'ocs.provisioning_api.users.getcurrentuser',
		'core.avatar.getavatar',
		'core.avatar.getavatardark',
		'core.guestavatar.getavatar',
		'core.guestavatar.getavatardark',
		'core.preview.getmimeiconurl',
		'ocs.core.apppassword.deleteapppassword',
		'ocs.core.apppassword.rotateapppassword',
		'core.wipe.checkwipe',
		'core.wipe.wipedone',
		'oauth2.oauthapi.gettoken',
		'theming.theming.getthemestylesheet',
		'theming.theming.getimage',
		'theming.theming.getmanifest',
		'theming.icon.getfavicon',
		'theming.icon.gettouchicon',
		'theming.icon.getthemedicon',
		'theming.usertheme.getbackground',
	];

	/** Route name prefixes refused inside apps a scope opens, since they hand files to other apps */
	private const REFUSED = [
		'ocs.files.template.',
		'ocs.files.directediting.',
		'files.directeditingview.',
		'ocs.files.conversionapi.',
	];

	/** Routes that need a scope other than the one opening their app */
	private const ROUTE_SCOPES = [
		'core.preview.getpreview' => TokenScopes::FILES_READ,
		'core.preview.getpreviewbyfileid' => TokenScopes::FILES_READ,
		'ocs.files_sharing.shareapi.createshare' => TokenScopes::FILES_SHARE,
		'ocs.files_sharing.shareapi.updateshare' => TokenScopes::FILES_SHARE,
		'ocs.files_sharing.shareapi.deleteshare' => TokenScopes::FILES_SHARE,
		'ocs.files_sharing.shareapi.sendshareemail' => TokenScopes::FILES_SHARE,
		'ocs.dav.direct.geturl' => TokenScopes::FILES_SHARE,
		'ocs.files_sharing.shareesapi.search' => TokenScopes::FILES_SHARE,
		'ocs.files_sharing.shareesapi.findrecommended' => TokenScopes::FILES_SHARE,
		'ocs.dav.calendarexport.export' => TokenScopes::CALENDAR_READ,
		'ocs.dav.upcoming_events.getevents' => TokenScopes::CALENDAR_READ,
		'ocs.dav.out_of_office.getcurrentoutofofficedata' => TokenScopes::CALENDAR_READ,
		'ocs.dav.out_of_office.getoutofoffice' => TokenScopes::CALENDAR_READ,
		'ocs.dav.federated_calendar.getpending' => TokenScopes::CALENDAR_READ,
		'ocs.dav.calendarimport.import' => TokenScopes::CALENDAR_WRITE,
		'ocs.dav.out_of_office.setoutofoffice' => TokenScopes::CALENDAR_WRITE,
		'ocs.dav.out_of_office.clearoutofoffice' => TokenScopes::CALENDAR_WRITE,
		'ocs.dav.federated_calendar.accept' => TokenScopes::CALENDAR_WRITE,
		'ocs.dav.federated_calendar.decline' => TokenScopes::CALENDAR_WRITE,
		'ocs.dav.contactsimport.import' => TokenScopes::CONTACTS_WRITE,
	];

	/**
	 * The scope that opens every route of these apps. They mix reads and writes, so it is
	 * write; read-only routes get their own entries once read-only tokens exist.
	 */
	private const WHOLE_APP_SCOPES = [
		'files' => TokenScopes::FILES_WRITE,
		'files_sharing' => TokenScopes::FILES_WRITE,
		'files_versions' => TokenScopes::FILES_WRITE,
		'files_trashbin' => TokenScopes::FILES_WRITE,
	];

	/** Routes where an admin may name another user to act as */
	private const ACT_AS_USER = [
		'ocs.dav.calendarexport.export',
		'ocs.dav.calendarimport.import',
		'ocs.dav.contactsimport.import',
	];

	/**
	 * @return list<string>|null the scopes the route needs, empty for infrastructure, null if refused
	 */
	public function requiredScopes(string $appId, string $route): ?array {
		if (in_array($route, self::INFRASTRUCTURE, true)) {
			return [];
		}
		foreach (self::REFUSED as $prefix) {
			if (str_starts_with($route, $prefix)) {
				return null;
			}
		}
		$scope = self::ROUTE_SCOPES[$route] ?? self::WHOLE_APP_SCOPES[$appId] ?? null;
		return $scope === null ? null : [$scope];
	}

	public function acceptsActAsUser(string $route): bool {
		return in_array($route, self::ACT_AS_USER, true);
	}
}
