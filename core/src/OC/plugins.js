/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default {

	/**
	 * @type {Array.<OC.Plugin>}
	 */
	_plugins: {},

	/**
	 * Register plugin
	 *
	 * @param {string} targetName app name / class name to hook into
	 * @param {OC.Plugin} plugin plugin
	 */
	register(targetName, plugin) {
		let plugins = this._plugins[targetName]
		if (!plugins) {
			plugins = this._plugins[targetName] = []
		}
		plugins.push(plugin)
	},

	/**
	 * Returns all plugin registered to the given target
	 * name / app name / class name.
	 *
	 * @param {string} targetName app name / class name to hook into
	 * @return {Array.<OC.Plugin>} array of plugins
	 */
	getPlugins(targetName) {
		return this._plugins[targetName] || []
	},

	/**
	 * Call attach() on all plugins registered to the given target name.
	 *
	 * @param {string} targetName app name / class name
	 * @param {object} targetObject to be extended
	 * @param {object} [options] options
	 */
	attach(targetName, targetObject, options) {
		const plugins = this.getPlugins(targetName)
		for (let i = 0; i < plugins.length; i++) {
			if (plugins[i].attach) {
				plugins[i].attach(targetObject, options)
			}
		}
	},

	/**
	 * Call detach() on all plugins registered to the given target name.
	 *
	 * @param {string} targetName app name / class name
	 * @param {object} targetObject to be extended
	 * @param {object} [options] options
	 */
	detach(targetName, targetObject, options) {
		const plugins = this.getPlugins(targetName)
		for (let i = 0; i < plugins.length; i++) {
			if (plugins[i].detach) {
				plugins[i].detach(targetObject, options)
			}
		}
	},

}
