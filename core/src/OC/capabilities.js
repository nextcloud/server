/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCapabilities as realGetCapabilities } from '@nextcloud/capabilities'
import logger from '../logger.js'

/**
 * Returns the capabilities
 *
 * @return {Array} capabilities
 *
 * @since 14.0.0
 */
export function getCapabilities() {
	if (OC.debug) {
		logger.warn('OC.getCapabilities is deprecated and will be removed in Nextcloud 21. See @nextcloud/capabilities')
	}
	return realGetCapabilities()
}
