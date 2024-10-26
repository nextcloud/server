/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import VendorBackbone from 'backbone'
import { davCall, davSync } from './backbone-webdav.js'

const Backbone = VendorBackbone.noConflict()

// Patch Backbone for DAV
Object.assign(Backbone, {
	davCall,
	davSync: davSync(Backbone),
})

export default Backbone
