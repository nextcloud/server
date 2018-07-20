/*
 * @Copyright 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Vincent Petry
 * @author Felix Nüsse <felix.nuesse@t-online.de>
 *
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function () {

	/**
	 * @class OCA.Files.Navigation
	 * @classdesc Navigation control for the files app sidebar.
	 *
	 * @param $el element containing the navigation
	 */
	var Navigation = function ($el) {
		this.initialize($el);
	};

	/**
	 * @memberof OCA.Files
	 */
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
		 * Key for the quick-acces-list
		 */
		$quickAccessListKey: 'sublist-favorites',
		/**
		 * Initializes the navigation from the given container
		 *
		 * @private
		 * @param $el element containing the navigation
		 */
		initialize: function ($el) {
			this.$el = $el;
			this._activeItem = null;
			this.$currentContent = null;
			this._setupEvents();

			this.setInitialQuickaccessSettings();
		},

		/**
		 * Setup UI events
		 */
		_setupEvents: function () {
			this.$el.on('click', 'li a', _.bind(this._onClickItem, this))
			this.$el.on('click', 'li button', _.bind(this._onClickMenuButton, this));
		},

		/**
		 * Returns the container of the currently active app.
		 *
		 * @return app container
		 */
		getActiveContainer: function () {
			return this.$currentContent;
		},

		/**
		 * Returns the currently active item
		 *
		 * @return item ID
		 */
		getActiveItem: function () {
			return this._activeItem;
		},

		/**
		 * Switch the currently selected item, mark it as selected and
		 * make the content container visible, if any.
		 *
		 * @param string itemId id of the navigation item to select
		 * @param array options "silent" to not trigger event
		 */
		setActiveItem: function (itemId, options) {
			var currentItem = this.$el.find('li[data-id=' + itemId + ']');
			var itemDir = currentItem.data('dir');
			var itemView = currentItem.data('view');
			var oldItemId = this._activeItem;
			if (itemId === this._activeItem) {
				if (!options || !options.silent) {
					this.$el.trigger(
						new $.Event('itemChanged', {
							itemId: itemId,
							previousItemId: oldItemId,
							dir: itemDir,
							view: itemView
						})
					);
				}
				return;
			}
			this.$el.find('li a').removeClass('active');
			if (this.$currentContent) {
				this.$currentContent.addClass('hidden');
				this.$currentContent.trigger(jQuery.Event('hide'));
			}
			this._activeItem = itemId;
			currentItem.children('a').addClass('active');
			this.$currentContent = $('#app-content-' + (typeof itemView === 'string' && itemView !== '' ? itemView : itemId));
			this.$currentContent.removeClass('hidden');
			if (!options || !options.silent) {
				this.$currentContent.trigger(jQuery.Event('show'));
				this.$el.trigger(
					new $.Event('itemChanged', {
						itemId: itemId,
						previousItemId: oldItemId,
						dir: itemDir,
						view: itemView
					})
				);
			}
		},

		/**
		 * Returns whether a given item exists
		 */
		itemExists: function (itemId) {
			return this.$el.find('li[data-id=' + itemId + ']').length;
		},

		/**
		 * Event handler for when clicking on an item.
		 */
		_onClickItem: function (ev) {
			var $target = $(ev.target);
			var itemId = $target.closest('li').attr('data-id');
			if (!_.isUndefined(itemId)) {
				this.setActiveItem(itemId);
			}
			ev.preventDefault();
		},

		/**
		 * Event handler for clicking a button
		 */
		_onClickMenuButton: function (ev) {
			var $target = $(ev.target);
			var $menu = $target.parent('li');
			var itemId = $target.closest('button').attr('id');

			var collapsibleToggles = [];
			var dotmenuToggles = [];

			if ($menu.hasClass('collapsible') && $menu.data('expandedstate')) {
				$menu.toggleClass('open');
				var show = $menu.hasClass('open') ? 1 : 0;
				var key = $menu.data('expandedstate');
				$.post(OC.generateUrl("/apps/files/api/v1/toggleShowFolder/" + key), {show: show});
			}

			dotmenuToggles.forEach(function foundToggle (item) {
				if (item[0] === ("#" + itemId)) {
					document.getElementById(item[1]).classList.toggle('open');
				}
			});

			ev.preventDefault();
		},

		/**
		 * Sort initially as setup of sidebar for QuickAccess
		 */
		setInitialQuickaccessSettings: function () {
			var quickAccesKey = this.$quickAccessListKey;
			var list = document.getElementById(quickAccesKey).getElementsByTagName('li');
			this.QuickSort(list, 0, list.length - 1);
		},

		/**
		 * Sorting-Algorithm for QuickAccess
		 */
		QuickSort: function (list, start, end) {
			var lastMatch;
			if (list.length > 1) {
				lastMatch = this.quicksort_helper(list, start, end);
				if (start < lastMatch - 1) {
					this.QuickSort(list, start, lastMatch - 1);
				}
				if (lastMatch < end) {
					this.QuickSort(list, lastMatch, end);
				}
			}
		},

		/**
		 * Sorting-Algorithm-Helper for QuickAccess
		 */
		quicksort_helper: function (list, start, end) {
			var pivot = Math.floor((end + start) / 2);
			var pivotElement = this.getCompareValue(list, pivot);
			var i = start;
			var j = end;

			while (i <= j) {
				while (this.getCompareValue(list, i) < pivotElement) {
					i++;
				}
				while (this.getCompareValue(list, j) > pivotElement) {
					j--;
				}
				if (i <= j) {
					this.swap(list, i, j);
					i++;
					j--;
				}
			}
			return i;
		},

		/**
		 * Sorting-Algorithm-Helper for QuickAccess
		 * This method allows easy access to the element which is sorted by.
		 */
		getCompareValue: function (nodes, int, strategy) {
				return nodes[int].getElementsByTagName('a')[0].innerHTML.toLowerCase();
		},

		/**
		 * Sorting-Algorithm-Helper for QuickAccess
		 * This method allows easy swapping of elements.
		 */
		swap: function (list, j, i) {
			list[i].before(list[j]);
			list[j].before(list[i]);
		}

	};

	OCA.Files.Navigation = Navigation;

})();





