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

	/**
	 * @class OCA.Files.Navigation
	 * @classdesc Navigation control for the files app sidebar.
	 *
	 * @param $el element containing the navigation
	 */
	var Navigation = function($el) {
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
		 * Initializes the navigation from the given container
		 *
		 * @private
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
			this.$el.on('click', 'li a', _.bind(this._onClickItem, this))
			this.$el.on('click', 'li button', _.bind(this._onClickMenuButton, this));
			this.$el.on('click', 'li input', _.bind(this._onClickMenuItem, this));
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
			var oldItemId = this._activeItem;
			if (itemId === this._activeItem) {
				if (!options || !options.silent) {
					this.$el.trigger(
						new $.Event('itemChanged', {itemId: itemId, previousItemId: oldItemId})
					);
				}
				return;
			}
			this.$el.find('li').removeClass('active');
			if (this.$currentContent) {
				this.$currentContent.addClass('hidden');
				this.$currentContent.trigger(jQuery.Event('hide'));
			}
			this._activeItem = itemId;
			this.$el.find('li[data-id=' + itemId + ']').addClass('active');
			this.$currentContent = $('#app-content-' + itemId);
			this.$currentContent.removeClass('hidden');
			if (!options || !options.silent) {
				this.$currentContent.trigger(jQuery.Event('show'));
				this.$el.trigger(
					new $.Event('itemChanged', {itemId: itemId, previousItemId: oldItemId})
				);
			}
		},

		/**
		 * Returns whether a given item exists
		 */
		itemExists: function(itemId) {
			return this.$el.find('li[data-id=' + itemId + ']').length;
		},

		/**
		 * Event handler for when clicking on an item.
		 */
		_onClickItem: function(ev) {
			var $target = $(ev.target);
			var itemId = $target.closest('li').attr('data-id');
			if (!_.isUndefined(itemId)) {
				this.setActiveItem(itemId);
			}
			ev.preventDefault();
		},
		/**
		 * Event handler for when clicking on an three-dot-menu.
		 */
		_onClickMenuButton: function(ev) {
			var $target = $(ev.target);
			var itemId = $target.closest('button').attr('id');
			if(itemId==='button-favorites'){
				document.getElementById('menu-favorites').classList.toggle('open');
			}
			ev.preventDefault();
		},

		/**
		 * Event handler for when clicking on a menuitem.
		 */
		_onClickMenuItem: function(ev) {


			var qaSelector= '#quickaccess-list';
			var qaKey= 'quickaccess-list';

			var $target = $(ev.target);
			var itemId = $target.closest('input').attr('id');

			if(itemId==='enableQuickAccess'){

				var qa =$(qaSelector).is(":visible");
				var url="/apps/files/api/v1/hidequickaccess";
				if(qa){
					url="/apps/files/api/v1/showquickaccess";
				}

				$.get(OC.generateUrl(url),function(data, status){
				});

				//begin sorting
				var elem = document.getElementById(qaKey);
				var list = elem.getElementsByTagName('li');
				document.getElementById('menu-favorites').classList.toggle('open');
				Quicksort(list,0, list.length);

				//
				//elem.empty();
				//end sorting


				$(qaSelector ).toggle();

			}
			ev.preventDefault();
		}

	};

	OCA.Files.Navigation = Navigation;

})();


function Quicksort(List, start, end) {

	//alert("length: "+(end-start));

	if((end-start)===1){
		alert("only one element to sort");
		return;
	}

	var parNode=List[0].parentNode;

	var pivot=((end-start)/2);
	var pivotelem=List[pivot].getAttribute('folderPos');
	alert("pivot: "+pivot+" cont: "+List[pivot].getAttribute('folderPos'));


	for (var i = 0; i < (end-start); i++) {
		alert("checking element: "+List[i].getAttribute('folderPos'));

		var currelem=List[i].getAttribute('folderPos');

		if(currelem >= pivotelem){
			alert("Element: "+currelem+" is bigger or equal than: "+pivotelem);
			parNode.insertBefore(List[i], List[pivot+1]);
			alert("Put "+currelem+" after "+ List[pivot-1].getAttribute('folderPos'))
		}else{
			alert("Element: "+currelem+" is smaller than: "+pivotelem);
			parNode.insertBefore(List[i], List[pivot]);
			alert("Put "+currelem+" before "+ List[pivot+1].getAttribute('folderPos'))
		}
	}

	Quicksort(List,0,pivot-1);
	Quicksort(List,pivot, end);

}