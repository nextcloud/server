/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
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
 *
 */

(function() {

	OCA.WorkflowEngine = OCA.WorkflowEngine || {};
	OCA.WorkflowEngine.Plugins = OCA.WorkflowEngine.Plugins || {};

	OCA.WorkflowEngine.Plugins.UserGroupMembershipPlugin = {
		getCheck: function() {
			return {
				'class': 'OCA\\WorkflowEngine\\Check\\UserGroupMembership',
				'name': t('workflowengine', 'User group membership'),
				'operators': [
					{'operator': 'is', 'name': t('workflowengine', 'is member of')},
					{'operator': '!is', 'name': t('workflowengine', 'is not member of')}
				]
			};
		},
		render: function(element, check) {
			if (check['class'] !== 'OCA\\WorkflowEngine\\Check\\UserGroupMembership') {
				return;
			}

			$(element).css('width', '400px');

			$(element).select2({
				ajax: {
					url: OC.generateUrl('settings/users/groups'),
					dataType: 'json',
					quietMillis: 100,
					data: function (term) {
						return {
							pattern: term, //search term
							filterGroups: true,
							sortGroups: 2 // by groupname
						};
					},
					results: function (response) {
						// TODO improve error case
						if (response.data === undefined) {
							console.error('Failure happened', response);
							return;
						}

						var results = [];

						// add admin groups
						$.each(response.data.adminGroups, function(id, group) {
							results.push({ id: group.id });
						});
						// add groups
						$.each(response.data.groups, function(id, group) {
							results.push({ id: group.id });
						});

						// TODO once limit and offset is implemented for groups we should paginate the search results
						return {
							results: results,
							more: false
						};
					}
				},
				initSelection: function (element, callback) {
					callback({id: element.val()});
				},
				formatResult: function (element) {
					return '<span>' + escapeHTML(element.id) + '</span>';
				},
				formatSelection: function (element) {
					return '<span title="'+escapeHTML(element.id)+'">'+escapeHTML(element.id)+'</span>';
				}
			});
		}
	};
})();

OC.Plugins.register('OCA.WorkflowEngine.CheckPlugins', OCA.WorkflowEngine.Plugins.UserGroupMembershipPlugin);
