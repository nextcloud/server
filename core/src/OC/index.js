/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { subscribe } from '@nextcloud/event-bus'

import { addScript, addStyle } from './legacy-loader.js'
import {
	ajaxConnectionLostHandler,
	processAjaxError,
	registerXHRForErrorProcessing,
} from './xhr-error.js'
import Apps from './apps.js'
import { AppConfig, appConfig } from './appconfig.js'
import { appSettings } from './appsettings.js'
import appswebroots from './appswebroots.js'
import Backbone from './backbone.js'
import {
	basename,
	dirname,
	encodePath,
	isSamePath,
	joinPaths,
} from '@nextcloud/paths'
import {
	build as buildQueryString,
	parse as parseQueryString,
} from './query-string.js'
import Config from './config.js'
import {
	coreApps,
	menuSpeed,
	PERMISSION_ALL,
	PERMISSION_CREATE,
	PERMISSION_DELETE,
	PERMISSION_NONE,
	PERMISSION_READ,
	PERMISSION_SHARE,
	PERMISSION_UPDATE,
	TAG_FAVORITE,
} from './constants.js'
import ContactsMenu from './contactsmenu.js'
import { currentUser, getCurrentUser } from './currentuser.js'
import Dialogs from './dialogs.js'
import EventSource from './eventsource.js'
import { get, set } from './get_set.js'
import { getCapabilities } from './capabilities.js'
import {
	getHost,
	getHostName,
	getPort,
	getProtocol,
} from './host.js'
import {
	getToken as getRequestToken,
} from './requesttoken.js'
import {
	hideMenus,
	registerMenu,
	showMenu,
	unregisterMenu,
} from './menu.js'
import { isUserAdmin } from './admin.js'
import L10N from './l10n.js'
import {
	getCanonicalLocale,
	getLanguage,
	getLocale,
} from '@nextcloud/l10n'

import {
	generateUrl,
	generateFilePath,
	generateOcsUrl,
	generateRemoteUrl,
	getRootUrl,
	imagePath,
	linkTo,
} from '@nextcloud/router'

import {
	linkToRemoteBase,
} from './routing.js'
import msg from './msg.js'
import Notification from './notification.js'
import PasswordConfirmation from './password-confirmation.js'
import Plugins from './plugins.js'
import { theme } from './theme.js'
import Util from './util.js'
import { debug } from './debug.js'
import { redirect, reload } from './navigation.js'
import webroot from './webroot.js'

/** @namespace OC */
export default {
	/*
	 * Constants
	 */
	coreApps,
	menuSpeed,
	PERMISSION_ALL,
	PERMISSION_CREATE,
	PERMISSION_DELETE,
	PERMISSION_NONE,
	PERMISSION_READ,
	PERMISSION_SHARE,
	PERMISSION_UPDATE,
	TAG_FAVORITE,

	/*
	 * Deprecated helpers to be removed
	 */
	/**
	 * Check if a user file is allowed to be handled.
	 *
	 * @param {string} file to check
	 * @return {boolean}
	 * @deprecated 17.0.0
	 */
	fileIsBlacklisted: file => !!(file.match(Config.blacklist_files_regex)),
	Apps,
	addScript,
	addStyle,
	AppConfig,
	appConfig,
	appSettings,
	appswebroots,
	Backbone,
	ContactsMenu,
	config: Config,
	/**
	 * Currently logged in user or null if none
	 *
	 * @type {string}
	 * @deprecated use `getCurrentUser` from https://www.npmjs.com/package/@nextcloud/auth
	 */
	currentUser,
	dialogs: Dialogs,
	EventSource,
	/**
	 * Returns the currently logged in user or null if there is no logged in
	 * user (public page mode)
	 *
	 * @since 9.0.0
	 * @deprecated 19.0.0 use `getCurrentUser` from https://www.npmjs.com/package/@nextcloud/auth
	 */
	getCurrentUser,
	isUserAdmin,
	L10N,

	/**
	 * Ajax error handlers
	 *
	 * @todo remove from here and keep internally -> requires new tests
	 */
	_ajaxConnectionLostHandler: ajaxConnectionLostHandler,
	_processAjaxError: processAjaxError,
	registerXHRForErrorProcessing,

	/**
	 * Capabilities
	 *
	 * @type {Array}
	 * @deprecated 20.0.0 use @nextcloud/capabilities instead
	 */
	getCapabilities,

	/*
	 * Legacy menu helpers
	 */
	hideMenus,
	registerMenu,
	showMenu,
	unregisterMenu,

	/*
	 * Path helpers
	 */
	/**
	 * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
	 */
	basename,
	/**
	 * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
	 */
	encodePath,
	/**
	 * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
	 */
	dirname,
	/**
	 * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
	 */
	isSamePath,
	/**
	 * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
	 */
	joinPaths,

	/**
	 * Host (url) helpers
	 */
	getHost,
	getHostName,
	getPort,
	getProtocol,

	/**
	 * @deprecated 20.0.0 use `getCanonicalLocale` from https://www.npmjs.com/package/@nextcloud/l10n
	 */
	getCanonicalLocale,
	/**
	 * @deprecated 26.0.0 use `getLocale` from https://www.npmjs.com/package/@nextcloud/l10n
	 */
	getLocale,
	/**
	 * @deprecated 26.0.0 use `getLanguage` from https://www.npmjs.com/package/@nextcloud/l10n
	 */
	getLanguage,

	/**
	 * Query string helpers
	 */
	buildQueryString,
	parseQueryString,

	msg,
	Notification,
	PasswordConfirmation,
	Plugins,
	theme,
	Util,
	debug,
	/**
	 * @deprecated 19.0.0 use `generateFilePath` from https://www.npmjs.com/package/@nextcloud/router
	 */
	filePath: generateFilePath,
	/**
	 * @deprecated 19.0.0 use `generateUrl` from https://www.npmjs.com/package/@nextcloud/router
	 */
	generateUrl,
	/**
	 * @deprecated 19.0.0 use https://lodash.com/docs#get
	 */
	get: get(window),
	/**
	 * @deprecated 19.0.0 use https://lodash.com/docs#set
	 */
	set: set(window),
	/**
	 * @deprecated 19.0.0 use `getRootUrl` from https://www.npmjs.com/package/@nextcloud/router
	 */
	getRootPath: getRootUrl,
	/**
	 * @deprecated 19.0.0 use `imagePath` from https://www.npmjs.com/package/@nextcloud/router
	 */
	imagePath,
	redirect,
	reload,
	requestToken: getRequestToken(),
	/**
	 * @deprecated 19.0.0 use `linkTo` from https://www.npmjs.com/package/@nextcloud/router
	 */
	linkTo,
	/**
	 * @param {string} service service name
	 * @param {number} version OCS API version
	 * @return {string} OCS API base path
	 * @deprecated 19.0.0 use `generateOcsUrl` from https://www.npmjs.com/package/@nextcloud/router
	 */
	linkToOCS: (service, version) => {
		return generateOcsUrl(service, {}, {
			ocsVersion: version || 1,
		}) + '/'
	},
	/**
	 * @deprecated 19.0.0 use `generateRemoteUrl` from https://www.npmjs.com/package/@nextcloud/router
	 */
	linkToRemote: generateRemoteUrl,
	linkToRemoteBase,
	/**
	 * Relative path to Nextcloud root.
	 * For example: "/nextcloud"
	 *
	 * @type {string}
	 *
	 * @deprecated 19.0.0 use `getRootUrl` from https://www.npmjs.com/package/@nextcloud/router
	 * @see OC#getRootPath
	 */
	webroot,
}

// Keep the request token prop in sync
subscribe('csrf-token-update', e => {
	OC.requestToken = e.token

	// Logging might help debug (Sentry) issues
	console.info('OC.requestToken changed', e.token)
})
