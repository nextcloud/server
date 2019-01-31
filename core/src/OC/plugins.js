/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @namespace OC.Plugins
 */

/**
 * @type Array.<OC.Plugin>
 * @todo make a real private
 */
export const _plugins = {};

/**
 * Register plugin
 *
 * @param {String} targetName app name / class name to hook into
 * @param {OC.Plugin} plugin
 */
export function register (targetName, plugin) {
	let plugins = _plugins[targetName];
	if (!plugins) {
		plugins = _plugins[targetName] = [];
	}
	plugins.push(plugin);
}

/**
 * Returns all plugin registered to the given target
 * name / app name / class name.
 *
 * @param {String} targetName app name / class name to hook into
 * @return {Array.<OC.Plugin>} array of plugins
 */
export function getPlugins (targetName) {
	return _plugins[targetName] || [];
}

/**
 * Call attach() on all plugins registered to the given target name.
 *
 * @param {String} targetName app name / class name
 * @param {Object} object to be extended
 * @param {Object} [options] options
 */
export function attach (targetName, targetObject, options) {
	var plugins = getPlugins(targetName);
	for (var i = 0; i < plugins.length; i++) {
		if (plugins[i].attach) {
			plugins[i].attach(targetObject, options);
		}
	}
}

/**
 * Call detach() on all plugins registered to the given target name.
 *
 * @param {String} targetName app name / class name
 * @param {Object} object to be extended
 * @param {Object} [options] options
 */
export function detach (targetName, targetObject, options) {
	const plugins = getPlugins(targetName);
	for (let i = 0; i < plugins.length; i++) {
		if (plugins[i].detach) {
			plugins[i].detach(targetObject, options);
		}
	}
}
