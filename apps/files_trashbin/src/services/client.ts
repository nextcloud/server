/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { davGetClient } from '@nextcloud/files'

// init webdav client
export const rootPath = `/trashbin/${getCurrentUser()?.uid}/trash`

export const client = davGetClient()
