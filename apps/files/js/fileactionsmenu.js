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
		'<a href="#" class="action action-{{nameLowerCase}} permanent" data-action="{{name}}">{{#if icon}}<img src="{{icon}}"/>{{else}}<span class="no-icon"></span>{{/if}}<span>{{displayName}}</span></a>' +
		'</li>' +
		'{{/each}}' +
		'</ul>';

	/**
	 * Construct a new FileActionsMenu instance
	 * @constructs FileActionsMenu
	 * @memberof OCA.Files
	 */
	var FileActionsMenu = function() {
		this.initialize();
	};

	FileActionsMenu.prototype = {
		$el: null,
		_template: null,

		/**
		 * Current context
		 *
		 * @type OCA.Files.FileActionContext
		 */
		_context: null,

		/**
		 * @private
		 */
		initialize: function(fileActions, fileList) {
			this.$el = $('<div class="fileActionsMenu dropdown hidden menu"></div>');
			this._template = Handlebars.compile(TEMPLATE_MENU);

			this.$el.on('click', 'a.action', _.bind(this._onClickAction, this));
			this.$el.on('afterHide', _.bind(this._onHide, this));
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
				item.nameLowerCase = item.name.toLowerCase();
				return item;
			});

			this.$el.empty();
			this.$el.append(this._template({
				items: items
			}));
		},

		/**
		 * Displays the menu under the given element
		 *
		 * @param {Object} $el target element
		 * @param {OCA.Files.FileActionContext} context context
		 */
		showAt: function($el, context) {
			this._context = context;

			this.render();
			this.$el.removeClass('hidden');

			$el.closest('td').append(this.$el);

			context.$file.addClass('mouseOver');

			OC.showMenu(null, this.$el);
		},

		/**
		 * Whenever the menu is hidden
		 */
		_onHide: function() {
			this._context.$file.removeClass('mouseOver');
			this.$el.remove();
		}
	};

	OCA.Files.FileActionsMenu = FileActionsMenu;

})();

