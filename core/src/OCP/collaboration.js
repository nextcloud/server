/*
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @typedef TypeDefinition
 * @method {callback} action This action is executed to let the user select a resource
 * @param {string} icon Contains the icon css class for the type
 * @constructor
 */

/**
 * @type {TypeDefinition[]}
 **/
let types = {};

/**
 * Those translations will be used by the vue component but they should be shipped with the server
 * t('core', 'Add to a collection')
 * t('core', 'Show details')
 * t('core', 'Hide details')
 * t('core', 'Rename collection')
 * t('core', 'Failed to rename collection')
 * t('core', 'Failed to create collection')
 * t('core', 'Failed to add resource to collection')
 */

export default {
	/**
	 *
	 * @param type
	 * @param {TypeDefinition} typeDefinition
	 */
	registerType(type, typeDefinition) {
		types[type] = typeDefinition;
	},
	trigger(type) {
		return types[type].action()
	},
	getTypes() {
		return Object.keys(types);
	},
	getIcon(type) {
		return types[type].typeIconClass || '';
	},
	getLabel(type) {
		return t('core', 'Link to a {label}', { label: types[type].typeString || type }, 1)
	},
	getLink(type, id) {
		/* TODO: Allow action to be executed instead of href as well */
		return typeof types[type] !== 'undefined' ? types[type].link(id) : '';
	}
};
