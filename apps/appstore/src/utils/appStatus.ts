/*!
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

/**
 * Check if an app can be installed.
 *
 * @param app - The app to check if installable
 */
export function canInstall(app: IAppstoreApp | IAppstoreExApp) {
	if (app.installed || app.internal) {
		return false
	}

	if (app.missingDependencies === undefined || app.missingDependencies.length === 0) {
		return true
	}

	if (!app.isCompatible && app.missingDependencies.length === 1) {
		// incompatible so can be installed but has to be force-enabled
		return true
	}

	return false
}

/**
 * Check if an app can be uninstalled.
 *
 * @param app - The app to check if uninstallable
 */
export function canUninstall(app: IAppstoreApp | IAppstoreExApp) {
	return app.installed && app.removable && !app.active
}

/**
 * Check if app can be enabled.
 *
 * @param app - The app to check
 */
export function canEnable(app: IAppstoreApp | IAppstoreExApp) {
	return canForceEnable(app) && app.isCompatible
}

/**
 * Check if an app can be force-enabled
 *
 * @param app - The app to check
 */
export function canForceEnable(app: IAppstoreApp | IAppstoreExApp) {
	return !app.active && (app.installed || canInstall(app))
}

/**
 * Check if an app needs to be force-enabled
 *
 * @param app - The app to check
 */
export function needForceEnable(app: IAppstoreApp | IAppstoreExApp) {
	return !app.active && !app.isCompatible
}

/**
 * Check if an app can be disabled.
 *
 * @param app - The app to check
 */
export function canDisable(app: IAppstoreApp | IAppstoreExApp) {
	return app.active && !app.internal
}

/**
 * Check if an app can be updated.
 *
 * @param app - The app to check if update-able
 */
export function canUpdate(app: IAppstoreApp | IAppstoreExApp) {
	return app.update !== undefined
}
