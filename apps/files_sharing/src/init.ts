/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { registerDavProperty } from '@nextcloud/files'
import registerSharingViews from './files_views/shares'

import './files_actions/acceptShareAction'
import './files_actions/openInFilesAction'
import './files_actions/rejectShareAction'
import './files_actions/restoreShareAction'
import './files_actions/sharingStatusAction'

registerSharingViews()

registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('oc:share-types', { oc: 'http://owncloud.org/ns' })
registerDavProperty('ocs:share-permissions', { ocs: 'http://open-collaboration-services.org/ns' })
