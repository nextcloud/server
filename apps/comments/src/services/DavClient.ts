/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getClient } from '@nextcloud/files/dav'
import { getRootPath } from '../utils/davUtils.ts'

const client = getClient(getRootPath())

export default client
