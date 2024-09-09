/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { registerViewerAction } from './files_actions/viewerAction'
import ViewerService from './services/Viewer.js'

// Register the files action
registerViewerAction()

// Init Viewer Service
window.OCA = window.OCA ?? {}
window.OCA.Viewer = new ViewerService()
window.OCA.Viewer.version = appVersion
