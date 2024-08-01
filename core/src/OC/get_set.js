/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export const get = context => name => {
	const namespaces = name.split('.')
	const tail = namespaces.pop()

	for (let i = 0; i < namespaces.length; i++) {
		context = context[namespaces[i]]
		if (!context) {
			return false
		}
	}
	return context[tail]
}

/**
 * Set a variable by name
 *
 * @param {string} context context
 * @return {Function} setter
 * @deprecated 19.0.0 use https://lodash.com/docs#set
 */
export const set = context => (name, value) => {
	const namespaces = name.split('.')
	const tail = namespaces.pop()

	for (let i = 0; i < namespaces.length; i++) {
		if (!context[namespaces[i]]) {
			context[namespaces[i]] = {}
		}
		context = context[namespaces[i]]
	}
	context[tail] = value
	return value
}
