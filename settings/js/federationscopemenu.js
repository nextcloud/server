/*
 * Copyright (c) 2016
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global OC, Handlebars */

(function() {

	var TEMPLATE_MENU =
		'<ul>' +
		'{{#each items}}' +
		'<li>' +
		'<a href="#" class="menuitem action action-{{name}} permanent" data-action="{{name}}">' +
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
	 * Construct a new FederationScopeMenu instance
	 * @constructs FederationScopeMenu
	 * @memberof OC.Settings
	 */
	var FederationScopeMenu = OC.Backbone.View.extend({
		tagName: 'div',
		className: 'federationScopeMenu popovermenu bubble hidden open menu',
		_scopes: [
			{
				name: 'private',
				displayName: t('core', 'Private'),
				icon: OC.imagePath('core', 'actions/password')
			},
			{
				name: 'contacts',
				displayName: t('core', 'Contacts'),
				icon: OC.imagePath('core', 'places/contacts-dark')
			},
			{
				name: 'public',
				displayName: t('core', 'Public'),
				icon: OC.imagePath('core', 'places/link')
			}
		],

		/**
		 * Current context
		 *
		 * @type OCA.Files.FileActionContext
		 */
		_context: null,

		events: {
			'click a.action': '_onClickAction'
		},

		template: Handlebars.compile(TEMPLATE_MENU),

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

			this.trigger('select:scope', $target.data('action'));

			OC.hideMenus();
		},

		/**
		 * Renders the menu with the currently set items
		 */
		render: function() {
			this.$el.html(this.template({
				items: this._scopes
			}));
		},

		/**
		 * Displays the menu
		 */
		show: function(context) {
			this._context = context;

			this.render();
			this.$el.removeClass('hidden');

			OC.showMenu(null, this.$el);
		}
	});

	OC.Settings = OC.Settings || {};
	OC.Settings.FederationScopeMenu = FederationScopeMenu;

})();

