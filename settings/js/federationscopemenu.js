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
		'<a href="#" class="menuitem action action-{{name}} permanent {{#if active}}active{{/if}}" data-action="{{name}}">' +
			'{{#if icon}}<img class="icon" src="{{icon}}"/>' +
			'{{else}}'+
				'{{#if iconClass}}' +
				'<span class="icon {{iconClass}}"></span>' +
				'{{else}}' +
				'<span class="no-icon"></span>' +
				'{{/if}}' +
			'{{/if}}' +
			'<p><strong class="menuitem-text">{{displayName}}</strong><br>' +
			'<span class="menuitem-text-detail">{{tooltip}}</span></p></a>' +
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
		className: 'federationScopeMenu popovermenu bubble hidden menu',
		field: undefined,
		_scopes: undefined,

		initialize: function(options) {
			this.field = options.field;
			this._scopes = [
				{
					name: 'private',
					displayName: (this.field === 'avatar' || this.field === 'displayname') ? t('core', 'Local') : t('core', 'Private'),
					tooltip: (this.field === 'avatar' || this.field === 'displayname') ? t('core', 'Only visible to local users') : t('core', 'Only visible to you'),
					icon: OC.imagePath('core', 'actions/password'),
					active: false
				},
				{
					name: 'contacts',
					displayName: t('core', 'Contacts'),
					tooltip: t('core', 'Visible to local users and to trusted servers'),
					icon: OC.imagePath('core', 'places/contacts-dark'),
					active: false
				},
				{
					name: 'public',
					displayName: t('core', 'Public'),
					tooltip: t('core', 'Will be synced to a global and public address book'),
					icon: OC.imagePath('core', 'places/link'),
					active: false
				}
			];
		},

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
			var currentlyActiveValue = $('#'+context.target.closest('form').id).find('.icon-checkmark > input')[0].value;

			for(var i in this._scopes) {
				this._scopes[i].active = false;
			}

			switch (currentlyActiveValue) {
				case "private":
					this._scopes[0].active = true;
					break;
				case "contacts":
					this._scopes[1].active = true;
					break;
				case "public":
					this._scopes[2].active = true;
					break;
			}

			var $el = $(context.target);
			var offsetIcon = $el.offset();
			var offsetHeading = $el.closest('h2').offset();

			this.render();
			this.$el.removeClass('hidden');

			OC.showMenu(null, this.$el);

			//Set the menuwidth
			var menuWidth = this.$el.width();
			this.$el.css('width', menuWidth);

			//Calculate menu position
			var l = offsetIcon.left - offsetHeading.left;
			l = l - (menuWidth / 2) + ($el.outerWidth()/2);
			this.$el.css('left', l);

		}
	});

	OC.Settings = OC.Settings || {};
	OC.Settings.FederationScopeMenu = FederationScopeMenu;

})();
