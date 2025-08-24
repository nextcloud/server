/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import escapeHTML from 'escape-html'

/**
 * @typedef TypeDefinition
 * @function action This action is executed to let the user select a resource
 * @param {string} icon Contains the icon css class for the type
 * @function Object() { [native code] }
 */

/**
 * @type {TypeDefinition[]}
 */
const types = {}

/**
 * Those translations will be used by the vue component but they should be shipped with the server
 * FIXME: Those translations should be added to the library
 *
 * @return {Array}
 */
export const l10nProjects = () => {
	return [
		t('core', 'Add to a project'),
		t('core', 'Show details'),
		t('core', 'Hide details'),
		t('core', 'Rename project'),
		t('core', 'Failed to rename the project'),
		t('core', 'Failed to create a project'),
		t('core', 'Failed to add the item to the project'),
		t('core', 'Connect items to a project to make them easier to find'),
		t('core', 'Type to search for existing projects'),
	]
}

export default {
	/**
	 *
	 * @param {string} type type
	 * @param {TypeDefinition} typeDefinition typeDefinition
	 */
	registerType(type, typeDefinition) {
		types[type] = typeDefinition
	},
	trigger(type) {
		return types[type].action()
	},
	getTypes() {
		return Object.keys(types)
	},
	getIcon(type) {
		return types[type].typeIconClass || ''
	},
	getLabel(type) {
		return escapeHTML(types[type].typeString || type)
	},
	getLink(type, id) {
		/* TODO: Allow action to be executed instead of href as well */
		return typeof types[type] !== 'undefined' ? types[type].link(id) : ''
	},
}
