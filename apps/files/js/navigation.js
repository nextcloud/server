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
			this.$el.on('click', 'li a', _.bind(this._onClickItem, this));
			this.$el.on('click', 'li button', _.bind(this._onClickMenuButton, this));

			var trashBinElement = $('.nav-trashbin');
			trashBinElement.droppable({
				over: function (event, ui) {
					trashBinElement.addClass('dropzone-background');
				},
				out: function (event, ui) {
					trashBinElement.removeClass('dropzone-background');
				},
				activate: function (event, ui) {
					var element = trashBinElement.find('a').first();
					element.addClass('nav-icon-trashbin-starred').removeClass('nav-icon-trashbin');
				},
				deactivate: function (event, ui) {
					var element = trashBinElement.find('a').first();
					element.addClass('nav-icon-trashbin').removeClass('nav-icon-trashbin-starred');
				},
				drop: function (event, ui) {
					trashBinElement.removeClass('dropzone-background');

					var $selectedFiles = $(ui.draggable);

					// FIXME: when there are a lot of selected files the helper
					// contains only a subset of them; the list of selected
					// files should be gotten from the file list instead to
					// ensure that all of them are removed.
					var item = ui.helper.find('tr');
					for (var i = 0; i < item.length; i++) {
						$selectedFiles.trigger('droppedOnTrash', item[i].getAttribute('data-file'), item[i].getAttribute('data-dir'));
					}
				}
			});
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
			var currentItem = this.$el.find('li[data-id="' + itemId + '"]');
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
				this.$currentContent.trigger(jQuery.Event('show', {
					itemId: itemId,
					previousItemId: oldItemId,
					dir: itemDir,
					view: itemView
				}));
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
			return this.$el.find('li[data-id="' + itemId + '"]').length;
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
			var quickAccessKey = this.$quickAccessListKey;
			var quickAccessMenu = document.getElementById(quickAccessKey);
			if (quickAccessMenu) {
				var list = quickAccessMenu.getElementsByTagName('li');
				this.QuickSort(list, 0, list.length - 1);
			}

			var favoritesListElement = $(quickAccessMenu).parent();
			favoritesListElement.droppable({
				over: function (event, ui) {
					favoritesListElement.addClass('dropzone-background');
				},
				out: function (event, ui) {
					favoritesListElement.removeClass('dropzone-background');
				},
				activate: function (event, ui) {
					var element = favoritesListElement.find('a').first();
					element.addClass('nav-icon-favorites-starred').removeClass('nav-icon-favorites');
				},
				deactivate: function (event, ui) {
					var element = favoritesListElement.find('a').first();
					element.addClass('nav-icon-favorites').removeClass('nav-icon-favorites-starred');
				},
				drop: function (event, ui) {
					favoritesListElement.removeClass('dropzone-background');

					var $selectedFiles = $(ui.draggable);

					if (ui.helper.find('tr').size() === 1) {
						var $tr = $selectedFiles.closest('tr');
						if ($tr.attr("data-favorite")) {
							return;
						}
						$selectedFiles.trigger('droppedOnFavorites', $tr.attr('data-file'));
					} else {
						// FIXME: besides the issue described for dropping on
						// the trash bin, for favoriting it is not possible to
						// use the data from the helper; due to some bugs the
						// tags are not always added to the selected files, and
						// thus that data can not be accessed through the helper
						// to prevent triggering the favorite action on an
						// already favorited file (which would remove it from
						// favorites).
						OC.Notification.showTemporary(t('files', 'You can only favorite a single file or folder at a time'));
					}
				}
			});
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
			var before = function(node, insertNode) {
				node.parentNode.insertBefore(insertNode, node);
			}
			before(list[i], list[j]);
			before(list[j], list[i]);
		}

	};

	OCA.Files.Navigation = Navigation;

})();





