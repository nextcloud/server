/*
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
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

import Vue from 'vue'
import { PopoverMenu } from 'nextcloud-vue'
import ClickOutside from 'vue-click-outside'
import { VTooltip } from 'v-tooltip'

Vue.prototype.t = t;

Vue.component('PopoverMenu', PopoverMenu)
Vue.directive('ClickOutside', ClickOutside)
Vue.directive('Tooltip', VTooltip)

import View from './views/CollaborationView'

let selectAction = {};
let icons = {};
let types = {};

window.Collaboration = {
	/**
	 *
	 * @param type
	 * @param {callback} selectCallback should return a promise
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
		return types[type].icon;
	},
	getLabel(type) {
		return t('files_sharing', 'Link to a {label}', { label: types[type].typeString || type }, 1)
	},
	getLink(type, id) {
		/* TODO: Allow action to be executed instead of href as well */
		return types[type].link(id);
	}
}

window.Collaboration.registerType('files', {
	action: () => {
		return new Promise((resolve, reject) => {
			OC.dialogs.filepicker('Link to a file', function (f) {
				const client = OC.Files.getClient();
				client.getFileInfo(f).then((status, fileInfo) => {
					resolve(fileInfo.id)
				}, () => {
					reject()
				})
			}, false);
		})
	},
	link: (id) => OC.generateUrl('/f/') + id,
	icon: 'nav-icon-files',
	/** used in "Link to a {typeString}" */
	typeString: 'file'
});

/* TODO: temporary data for testing */
window.Collaboration.registerType('calendar', {
	action: () => {
		return new Promise((resolve, reject) => {
			var id = window.prompt("calendar id", "1");
			resolve(id);
		})
	},
	icon: 'icon-calendar-dark',
	typeName: 'calendar',
});
window.Collaboration.registerType('contact', {
	action: () => {
		return new Promise((resolve, reject) => {
			var id = window.prompt("contacts id", "1");
			resolve(id);
		})
	},
	icon: 'icon-contacts-dark',
	typeName: 'contact',
});

export { Vue, View }
