/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { AuthMechanism } from './services/AuthMachanism.ts'

window.OCA.FilesExternal ??= {}
window.OCA.FilesExternal.AuthMechanism = new AuthMechanism()
