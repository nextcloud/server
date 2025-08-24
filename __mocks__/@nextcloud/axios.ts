/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
export default {
	interceptors: {
		response: {
			use: () => {},
		},
		request: {
			use: () => {},
		},
	},
	get: async () => ({ status: 200, data: {} }),
	delete: async () => ({ status: 200, data: {} }),
	post: async () => ({ status: 200, data: {} }),
}
