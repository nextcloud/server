<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Token\TokenRoutes;
use OC\Authentication\Token\TokenScopes;
use OC\Route\Router;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\RequestContext;
use Test\TestCase;

class TokenRoutesTest extends TestCase {
	public static function routes(): array {
		return [
			'capabilities' => ['core', 'ocs.core.ocs.getcapabilities', []],
			'avatar' => ['core', 'core.avatar.getavatar', []],
			'revoke itself' => ['core', 'ocs.core.apppassword.deleteapppassword', []],
			'theming background' => ['theming', 'theming.usertheme.getbackground', []],
			'mime icon' => ['core', 'core.preview.getmimeiconurl', []],
			'avatar upload' => ['core', 'core.avatar.postavatar', null],
			'unified search' => ['core', 'ocs.core.unifiedsearch.search', null],
			'notifications' => ['notifications', 'ocs.notifications.endpoint.listnotifications', null],
			'files api' => ['files', 'ocs.files.api.getrecentfiles', [TokenScopes::FILES_WRITE]],
			'versions' => ['files_versions', 'files_versions.preview.getpreview', [TokenScopes::FILES_WRITE]],
			'share list' => ['files_sharing', 'ocs.files_sharing.shareapi.getshares', [TokenScopes::FILES_WRITE]],
			'out of office read' => ['dav', 'ocs.dav.out_of_office.getoutofoffice', [TokenScopes::CALENDAR_READ]],
			'out of office write' => ['dav', 'ocs.dav.out_of_office.setoutofoffice', [TokenScopes::CALENDAR_WRITE]],
			'other dav route' => ['dav', 'dav.invitation_response.accept', null],
			'unknown app' => ['deck', 'ocs.deck.board.index', null],
		];
	}

	#[DataProvider('routes')]
	public function testRequiredScopes(string $appId, string $route, ?array $expected): void {
		$this->assertSame($expected, (new TokenRoutes())->requiredScopes($appId, $route));
	}

	public static function strictEndpoints(): array {
		return [
			'preview' => ['GET', '/core/preview.png', 'core', [TokenScopes::FILES_READ], false],
			'preview by file id' => ['GET', '/core/preview', 'core', [TokenScopes::FILES_READ], false],
			'create share' => ['POST', '/ocsapp/apps/files_sharing/api/v1/shares', 'files_sharing', [TokenScopes::FILES_SHARE], false],
			'update share' => ['PUT', '/ocsapp/apps/files_sharing/api/v1/shares/1', 'files_sharing', [TokenScopes::FILES_SHARE], false],
			'delete share' => ['DELETE', '/ocsapp/apps/files_sharing/api/v1/shares/1', 'files_sharing', [TokenScopes::FILES_SHARE], false],
			'share email' => ['POST', '/ocsapp/apps/files_sharing/api/v1/shares/1/send-email', 'files_sharing', [TokenScopes::FILES_SHARE], false],
			'direct link' => ['POST', '/ocsapp/apps/dav/api/v1/direct', 'dav', [TokenScopes::FILES_SHARE], false],
			'sharee search' => ['GET', '/ocsapp/apps/files_sharing/api/v1/sharees', 'files_sharing', [TokenScopes::FILES_SHARE], false],
			'sharee recommendations' => ['GET', '/ocsapp/apps/files_sharing/api/v1/sharees_recommended', 'files_sharing', [TokenScopes::FILES_SHARE], false],
			'file templates' => ['POST', '/ocsapp/apps/files/api/v1/templates/create', 'files', null, false],
			'direct editing' => ['GET', '/ocsapp/apps/files/api/v1/directEditing', 'files', null, false],
			'direct editing page' => ['GET', '/apps/files/directEditing/abc', 'files', null, false],
			'file conversion' => ['POST', '/ocsapp/apps/files/api/v1/convert', 'files', null, false],
			'calendar export' => ['POST', '/ocsapp/calendar/export', 'dav', [TokenScopes::CALENDAR_READ], true],
			'calendar import' => ['POST', '/ocsapp/calendar/import', 'dav', [TokenScopes::CALENDAR_WRITE], true],
			'contacts import' => ['POST', '/ocsapp/contacts/import', 'dav', [TokenScopes::CONTACTS_WRITE], true],
		];
	}

	/**
	 * Resolves real URLs, so a renamed route fails here instead of falling back to its app's scope
	 */
	#[DataProvider('strictEndpoints')]
	public function testStrictEndpointsKeepTheirRule(string $method, string $url, string $appId, ?array $scopes, bool $actAs): void {
		$router = clone Server::get(Router::class);
		$router->setContext(new RequestContext(method: $method));
		$route = $router->findMatchingRoute($url)['_route'];

		$routes = new TokenRoutes();
		$this->assertSame($scopes, $routes->requiredScopes($appId, $route));
		$this->assertSame($actAs, $routes->acceptsActAsUser($route));
	}
}
