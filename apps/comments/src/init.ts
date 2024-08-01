/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { registerFileAction } from '@nextcloud/files'
import { action } from './actions/inlineUnreadCommentsAction'

registerFileAction(action)
