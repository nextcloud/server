/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getClient, defaultRootPath, getRemoteURL } from '@nextcloud/files/dav'

const davRemote = getRemoteURL()

export const client = getClient(`${davRemote}${defaultRootPath}`)
