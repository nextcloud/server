/*
 * Copyright (c) 2018
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global Handlebars */
(function() {
	var TEMPLATE_MENU =
		'<ul>' +
		'{{#each items}}' +
		'<li>' +
		'<a href="#" class="menuitem action {{name}} permanent" data-action="{{name}}">' +
			'{{#if iconClass}}' +
				'<span class="icon {{iconClass}}"></span>' +
			'{{else}}' +
				'<span class="no-icon"></span>' +
			'{{/if}}' +
			'<span>{{displayName}}</span>' +
		'</li>' +
		'{{/each}}' +
		'</ul>';

	/**
	 * Construct a new CommentsModifyMenuinstance
	 * @constructs CommentsModifyMenu
	 * @memberof OC.Comments
	 */
	var CommentsModifyMenu = OC.Backbone.View.extend({
		tagName: 'div',
		className: 'commentsModifyMenu popovermenu bubble menu',
		_scopes: [
			{
				name: 'edit',
				displayName:  t('comments', 'Edit comment'),
				iconClass: 'icon-rename'
			},
			{
				name: 'delete',
				displayName: t('comments', 'Delete comment'),
				iconClass: 'icon-delete'
			}
		],
		initialize: function() {

		},
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

			OC.hideMenus();

			this.trigger('select:menu-item-clicked', event, $target.data('action'));
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

			for(var i in this._scopes) {
				this._scopes[i].active = false;
			}


			var $el = $(context.target);
			var offsetIcon = $el.offset();
			var offsetContainer = $el.closest('.authorRow').offset();

			// adding some extra top offset to push the menu below the button.
			var position = {
				top: offsetIcon.top - offsetContainer.top + 48,
				left: '',
				right: ''
			};

			position.left = offsetIcon.left - offsetContainer.left;

			if (position.left > 200) {
				// we need to position the menu to the right.
				position.left = '';
				position.right = this.$el.closest('.comment').find('.date').width();
				this.$el.removeClass('menu-left').addClass('menu-right');
			} else {
				this.$el.removeClass('menu-right').addClass('menu-left');
			}
			this.$el.css(position);
			this.render();
			this.$el.removeClass('hidden');

			OC.showMenu(null, this.$el);
		}
	});

	OCA.Comments = OCA.Comments || {};
	OCA.Comments.CommentsModifyMenu = CommentsModifyMenu;
})(OC, OCA);