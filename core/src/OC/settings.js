/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import $ from 'jquery'
import _ from 'underscore'
import logger from '../logger.js'

/**
 * @deprecated 25.0.0 Use Vue based (`@nextcloud/vue`) settings components instead
 */
export default {
	_cachedGroups: null,

	escapeHTML: function(text) {
		return text.toString()
			.split('&').join('&amp;')
			.split('<').join('&lt;')
			.split('>').join('&gt;')
			.split('"').join('&quot;')
			.split('\'').join('&#039;')
	},

	async rebuildNavigation() {
		const { data } = await axios.get(generateOcsUrl('core/navigation', 2) + '/apps?format=json')
		if (data.ocs.meta.statuscode !== 200) {
			return
		}

		emit('nextcloud:app-menu.refresh', { apps: data.ocs.data })
		window.dispatchEvent(new Event('resize'))
	},

	/**
	 * Setup selection box for group selection.
	 *
	 * Values need to be separated by a pipe "|" character.
	 * (mostly because a comma is more likely to be used
	 * for groups)
	 *
	 * @param $elements jQuery element (hidden input) to setup select2 on
	 * @param {Array} [extraOptions] extra options hash to pass to select2
	 * @param {Array} [options] extra options
	 * @param {Array} [options.excludeAdmins] flag whether to exclude admin groups
	 */
	setupGroupsSelect: function($elements, extraOptions, options) {
		const self = this
		options = options || {}
		if ($elements.length > 0) {
			// Let's load the data and THEN init our select
			$.ajax({
				url: generateOcsUrl('cloud/groups/details'),
				dataType: 'json',
				success: function(data) {
					const results = []

					if (data.ocs.data.groups && data.ocs.data.groups.length > 0) {
						data.ocs.data.groups.forEach(function(group) {
							if (!options.excludeAdmins || group.id !== 'admin') {
								results.push({ id: group.id, displayname: group.displayname })
							}
						})

						// note: settings are saved through a "change" event registered
						// on all input fields
						$elements.select2(_.extend({
							placeholder: t('core', 'Groups'),
							allowClear: true,
							multiple: true,
							toggleSelect: true,
							separator: '|',
							data: { results, text: 'displayname' },
							initSelection: function(element, callback) {
								const groups = $(element).val()
								let selection
								if (groups && results.length > 0) {
									selection = _.map(_.filter((groups || []).split('|').sort(), function(groupId) {
										return results.find(function(group) {
											return group.id === groupId
										}) !== undefined
									}), function(groupId) {
										return {
											id: groupId,
											displayname: results.find(function(group) {
												return group.id === groupId
											}).displayname,
										}
									})
								} else if (groups) {
									selection = _.map((groups || []).split('|').sort(), function(groupId) {
										return {
											id: groupId,
											displayname: groupId,
										}
									})
								}
								callback(selection)
							},
							formatResult: function(element) {
								return self.escapeHTML(element.displayname)
							},
							formatSelection: function(element) {
								return self.escapeHTML(element.displayname)
							},
							escapeMarkup: function(m) {
								// prevent double markup escape
								return m
							},
						}, extraOptions || {}))
					} else {
						OC.Notification.show(t('core', 'Group list is empty'), { type: 'error' })
						logger.debug(data)
					}
				},
				error: function(data) {
					OC.Notification.show(t('core', 'Unable to retrieve the group list'), { type: 'error' })
					logger.debug(data)
				},
			})
		}
	},
}
