/*
 * Copyright (c) 2014
 *
 * @author Vincent Petry
 * @copyright 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {

	var Navigation = function($el) {
		this.initialize($el);
	};

	Navigation.prototype = {

		/**
		 * Currently selected item in the list
		 */
		_activeItem: null,

		/**
		 * Currently selected container
		 */
		$currentContent: null,

		/**
		 * Initializes the navigation from the given container
		 * @param $el element containing the navigation
		 */
		initialize: function($el) {
			this.$el = $el;
			this._activeItem = null;
			this.$currentContent = null;
			this._setupEvents();
		},

		/**
		 * Setup UI events
		 */
		_setupEvents: function() {
			this.$el.on('click', 'li a', _.bind(this._onClickItem, this));
		},

		/**
		 * Returns the container of the currently active app.
		 *
		 * @return app container
		 */
		getActiveContainer: function() {
			return this.$currentContent;
		},

		/**
		 * Returns the currently active item
		 * 
		 * @return item ID
		 */
		getActiveItem: function() {
			return this._activeItem;
		},

		/**
		 * Switch the currently selected item, mark it as selected and
		 * make the content container visible, if any.
		 *
		 * @param string itemId id of the navigation item to select
		 * @param array options "silent" to not trigger event
		 */
		setActiveItem: function(itemId, options) {
			if (itemId === this._activeItem) {
				return;
			}
			this._activeItem = itemId;
			this.$el.find('li').removeClass('selected');
			if (this.$currentContent) {
				this.$currentContent.addClass('hidden');
				this.$currentContent.trigger(jQuery.Event('hide'));
			}
			this.$currentContent = $('#app-content-' + itemId);
			this.$currentContent.removeClass('hidden');
			this.$el.find('li[data-id=' + itemId + ']').addClass('selected');
			if (!options || !options.silent) {
				this.$currentContent.trigger(jQuery.Event('show'));
				this.$el.trigger(new $.Event('itemChanged', {itemId: itemId}));
			}
		},

		/**
		 * Event handler for when clicking on an item.
		 */
		_onClickItem: function(ev) {
			var $target = $(ev.target);
			var itemId = $target.closest('li').attr('data-id');
			this.setActiveItem(itemId);
			return false;
		}
	};

	OCA.Files.Navigation = Navigation;

})();
