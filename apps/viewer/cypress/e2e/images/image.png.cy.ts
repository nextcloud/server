/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import imageTest from '../mixins/image'

describe('Open image.png in viewer', function() {
	imageTest('image.png', 'image/png')
})
