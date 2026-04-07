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

// Eagerly register any handlers queued before the viewer was initialized.
// This must happen synchronously so that OCA.Viewer.mimetypes is complete
// before the file action's enabled() callback is evaluated by the files app.
if (window._oca_viewer_handlers) {
	window._oca_viewer_handlers.forEach(handler => window.OCA.Viewer.registerHandler(handler))
}
