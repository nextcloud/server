/*
 * Copyright (c) 2018
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
		'<li class="item-{{name}}">' +
		'<a href="#" class="menuitem action {{name}} permanent" data-action="{{name}}">' +
			'{{#if iconClass}}' +
				'<span class="icon {{iconClass}}"></span>' +
			'{{else}}' +
				'<span class="no-icon"></span>' +
			'{{/if}}' +
			'<span class="label">{{displayName}}</span>' +
		'</li>' +
		'{{/each}}' +
		'</ul>';

	var FileSelectionMenu = OC.Backbone.View.extend({
		tagName: 'div',
		className: 'filesSelectMenu popovermenu bubble menu-center',
		_scopes: null,
		/**
		 * Event handler whenever an action has been clicked within the menu
		 *
		 * @param {Object} event event object
		 */
		_onClickAction: function(event) {
			var $target = $(event.currentTarget);
			if (!$target.hasClass('menuitem')) {
				$target = $target.closest('.menuitem');
			}

			OC.hideMenus();

			var action = $target.data('action');
			if (!action) {
				return;
			}

			for (var i = 0; i !== this._scopes.length; ++i) {
				var name = this._scopes[i].name;
				var method = this._scopes[i].method;
				if (name === action) {
					method(event);
					break;
				}
			}

		},
		initialize: function(menuItems) {
			console.log('init-fileseleectionmenu');
			console.log(menuItems);
			this._scopes = menuItems;
		},
		events: {
			'click a.action': '_onClickAction'
		},
		template: Handlebars.compile(TEMPLATE_MENU),
		/**
		 * Renders the menu with the currently set items
		 */
		render: function() {
			this.$el.html(this.template({
				items: this._scopes
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
			return false;
		},
		toggleItemVisibility: function (itemName, hide) {
			this.$el.find('.item-' + itemName).toggleClass('hidden', hide);
		},
		updateItemText: function (itemName, translation) {
			this.$el.find('.item-' + itemName).find('label').text(translation);
		}
	});

	OCA.Files.FileSelectionMenu = FileSelectionMenu;
})(OC, OCA);