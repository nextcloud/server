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
		_selectedItem: null,

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
			this._selectedItem = null;
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
		 * Switch the currently selected item, mark it as selected and
		 * make the content container visible, if any.
		 * @param string itemId id of the navigation item to select
		 */
		setSelectedItem: function(itemId) {
			if (itemId === this._selectedItem) {
				return;
			}
			this._selectedItem = itemId;
			this.$el.find('li').removeClass('selected');
			if (this.$currentContent) {
				this.$currentContent.addClass('hidden');
			}
			this.$currentContent = $('#app-content-' + itemId);
			this.$currentContent.removeClass('hidden');
			this.$el.find('li[data-id=' + itemId + ']').addClass('selected');
		},

		/**
		 * Event handler for when clicking on an item.
		 */
		_onClickItem: function(ev) {
			var $target = $(ev.target);
			var itemId = $target.closest('li').attr('data-id');
			this.setSelectedItem(itemId);
		}
	};

	OCA.Files.Navigation = Navigation;

})();
