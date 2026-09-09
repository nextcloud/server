/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Importing the viewer registers the handlers for images, video and audio,
// and offers this copy as the page's implementation. Nothing of the viewer
// itself is loaded until a file is opened.
//
// This runs from an init script so those handlers, and the file actions they
// register, are in place before the Files list takes its first snapshot of
// the available actions.
import '@nextcloud/viewer'
