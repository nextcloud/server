/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {

	var TEMPLATE_MENU =
		'<ul>' +
		'{{#each items}}' +
		'<li>' +
		'<a href="#" class="menuitem action action-{{nameLowerCase}} permanent" data-action="{{name}}">' +
			'{{#if icon}}<img class="icon" src="{{icon}}"/>' +
			'{{else}}'+
				'{{#if iconClass}}' +
				'<span class="icon {{iconClass}}"></span>' +
				'{{else}}' +
				'<span class="no-icon"></span>' +
				'{{/if}}' +
			'{{/if}}' +
			'<span>{{displayName}}</span></a>' +
		'</li>' +
		'{{/each}}' +
		'</ul>';

	/**
	 * Construct a new FileActionsMenu instance
	 * @constructs FileActionsMenu
	 * @memberof OCA.Files
	 */
	var FileActionsMenu = OC.Backbone.View.extend({
		tagName: 'div',
		className: 'fileActionsMenu popovermenu bubble hidden open menu',

		/**
		 * Current context
		 *
		 * @type OCA.Files.FileActionContext
		 */
		_context: null,

		events: {
			'click a.action': '_onClickAction'
		},

		template: function(data) {
			if (!OCA.Files.FileActionsMenu._TEMPLATE) {
				OCA.Files.FileActionsMenu._TEMPLATE = Handlebars.compile(TEMPLATE_MENU);
			}
			return OCA.Files.FileActionsMenu._TEMPLATE(data);
		},

		/**
		 * Event handler whenever an action has been clicked within the menu
		 *
		 * @param {Object} event event object
		 */
		_onClickAction: function(event) {
			var $target = $(event.target);
			if (!$target.is('a')) {
				$target = $target.closest('a');
			}
			var fileActions = this._context.fileActions;
			var actionName = $target.attr('data-action');
			var actions = fileActions.getActions(
				fileActions.getCurrentMimeType(),
				fileActions.getCurrentType(),
				fileActions.getCurrentPermissions()
			);
			var actionSpec = actions[actionName];
			var fileName = this._context.$file.attr('data-file');

			event.stopPropagation();
			event.preventDefault();

			OC.hideMenus();

			actionSpec.action(
				fileName,
				this._context
			);
		},

		/**
		 * Renders the menu with the currently set items
		 */
		render: function() {
			var self = this;
			var fileActions = this._context.fileActions;
			var actions = fileActions.getActions(
				fileActions.getCurrentMimeType(),
				fileActions.getCurrentType(),
				fileActions.getCurrentPermissions()
			);

			var defaultAction = fileActions.getDefaultFileAction(
				fileActions.getCurrentMimeType(),
				fileActions.getCurrentType(),
				fileActions.getCurrentPermissions()
			);

			var items = _.filter(actions, function(actionSpec) {
				return (
					actionSpec.type === OCA.Files.FileActions.TYPE_DROPDOWN &&
					(!defaultAction || actionSpec.name !== defaultAction.name)
				);
			});
			items = _.map(items, function(item) {
				if (_.isFunction(item.displayName)) {
					item = _.extend({}, item);
					item.displayName = item.displayName(self._context);
				}
				return item;
			});
			items = items.sort(function(actionA, actionB) {
				var orderA = actionA.order || 0;
				var orderB = actionB.order || 0;
				if (orderB === orderA) {
					return OC.Util.naturalSortCompare(actionA.displayName, actionB.displayName);
				}
				return orderA - orderB;
			});
			items = _.map(items, function(item) {
				item.nameLowerCase = item.name.toLowerCase();
				return item;
			});

			this.$el.html(this.template({
				items: items
			}));
		},

		/**
		 * Displays the menu under the given element
		 *
		 * @param {OCA.Files.FileActionContext} context context
		 * @param {Object} $trigger trigger element
		 */
		show: function(context) {
			this._context = context;

			this.render();
			this.$el.removeClass('hidden');

			OC.showMenu(null, this.$el);
		}
	});

	OCA.Files.FileActionsMenu = FileActionsMenu;

})();

