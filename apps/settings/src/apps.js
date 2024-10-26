/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import rebuildNavigation from './service/rebuild-navigation.js'

window.OC.Settings = window.OC.Settings || {}
window.OC.Settings.Apps = window.OC.Settings.Apps || {
	rebuildNavigation,
}
