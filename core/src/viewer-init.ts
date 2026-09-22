/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { registerDefaultHandlers } from '@nextcloud/viewer'
import { installLegacyViewerApi } from './viewer-legacy.ts'

// Registers the handlers for images, video and audio, and offers this copy of
// the viewer to the page. Nothing of the viewer itself is loaded until a file
// is opened.
//
// This runs from an init script so those handlers, and the file actions they
// register, are in place before the Files list takes its first snapshot of the
// available actions.
registerDefaultHandlers()

// Nothing here calls OCA.Viewer any more. It stays on the page for apps that
// have not moved over, warns whenever it is touched, and goes in Nextcloud 40.
installLegacyViewerApi()
