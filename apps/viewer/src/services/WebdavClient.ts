/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { davGetClient, davRootPath, davGetRemoteURL } from '@nextcloud/files'

const davRemote = davGetRemoteURL()

export const client = davGetClient(`${davRemote}${davRootPath}`)
