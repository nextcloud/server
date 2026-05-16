/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import imageTest from '../mixins/image'

describe('Open image.webp in viewer', function() {
	imageTest('image.webp', 'image/webp')
})
