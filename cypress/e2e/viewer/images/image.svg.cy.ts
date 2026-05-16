/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import imageTest from '../mixins/image'

describe('Open image.svg in viewer', function() {
	imageTest('image.svg', 'image/svg+xml', 'data:image/svg+xml;base64')
})
