/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2014 Vincent Petry <pvince81@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

(function() {
	/**
	 * @class BreadCrumb
	 * @memberof OCA.Files
	 * @classdesc Breadcrumbs that represent the current path.
	 *
	 * @param {Object} [options] options
	 * @param {Function} [options.onClick] click event handler
	 * @param {Function} [options.onDrop] drop event handler
	 * @param {Function} [options.getCrumbUrl] callback that returns
	 * the URL of a given breadcrumb
	 */
	var BreadCrumb = function(options){
		this.$el = $('<div class="breadcrumb"></div>');
		this.$menu = $('<div class="popovermenu menu menu-center"><ul></ul></div>');
		options = options || {};
		if (options.onClick) {
			this.onClick = options.onClick;
		}
		if (options.onDrop) {
			this.onDrop = options.onDrop;
			this.onOver = options.onOver;
			this.onOut = options.onOut;
		}
		if (options.getCrumbUrl) {
			this.getCrumbUrl = options.getCrumbUrl;
		}
		this._detailViews = [];
	};

	/**
	 * @memberof OCA.Files
	 */
	BreadCrumb.prototype = {
		$el: null,
		dir: null,
		dirInfo: null,

		/**
		 * Total width of all breadcrumbs
		 * @type int
		 * @private
		 */
		totalWidth: 0,
		breadcrumbs: [],
		onClick: null,
		onDrop: null,
		onOver: null,
		onOut: null,

		/**
		 * Sets the directory to be displayed as breadcrumb.
		 * This will re-render the breadcrumb.
		 * @param dir path to be displayed as breadcrumb
		 */
		setDirectory: function(dir) {
			dir = dir.replace(/\\/g, '/');
			dir = dir || '/';
			if (dir !== this.dir) {
				this.dir = dir;
				this.render();
			}
		},

		setDirectoryInfo: function(dirInfo) {
			if (dirInfo !== this.dirInfo) {
				this.dirInfo = dirInfo;
				this.render();
			}
		},

		/**
		 * @param {Backbone.View} detailView
		 */
		addDetailView: function(detailView) {
			this._detailViews.push(detailView);
		},

		/**
		 * Returns the full URL to the given directory
		 *
		 * @param {Object.<String, String>} part crumb data as map
		 * @param {int} index crumb index
		 * @return full URL
		 */
		getCrumbUrl: function(part, index) {
			return '#';
		},

		/**
		 * Renders the breadcrumb elements
		 */
		render: function() {
			var parts = this._makeCrumbs(this.dir || '/');
			var $crumb;
			var $menuItem;
			this.$el.empty();
			this.breadcrumbs = [];

			for (var i = 0; i < parts.length; i++) {
				var part = parts[i];
				var $image;
				var $link = $('<a></a>');
				$crumb = $('<div class="crumb svg"></div>');
				if(part.dir) {
					$link.attr('href', this.getCrumbUrl(part, i));
				}
				if(part.name) {
					$link.text(part.name);
				}
				$link.addClass(part.linkclass);
				$crumb.append($link);
				$crumb.data('dir', part.dir);
				// Ignore menu button
				$crumb.data('crumb-id', i - 1);
				$crumb.addClass(part.class);

				if (part.img) {
					$image = $('<img class="svg"></img>');
					$image.attr('src', part.img);
					$image.attr('alt', part.alt);
					$link.append($image);
				}
				this.breadcrumbs.push($crumb);
				this.$el.append($crumb);
				if (this.onClick) {
					$crumb.on('click', this.onClick);
				}
			}

				var err = new Error();
				console.log(err.stack);
			// Menu creation
			this._createMenu();
			for (var i = 0; i < parts.length; i++) {
				var part = parts[i];
				if(part.dir) {
					$menuItem = $('<li><a><span></span></a></li>');
					$menuItem.find('a').attr('href', this.getCrumbUrl(part, i));
					$menuItem.find('span').text(part.name);
					this.$menu.children('ul').append($menuItem);
					if (this.onClick) {
						$menuItem.on('click', this.onClick);
					}
				}
			}

			_.each(this._detailViews, function(view) {
				view.render({
					dirInfo: this.dirInfo
				});
				$crumb.append(view.$el);
			}, this);

			// in case svg is not supported by the browser we need to execute the fallback mechanism
			if (!OC.Util.hasSVGSupport()) {
				OC.Util.replaceSVG(this.$el);
			}

			// setup drag and drop
			if (this.onDrop) {
				this.$el.find('.crumb:not(.last)').droppable({
					drop: this.onDrop,
					over: this.onOver,
					out: this.onOut,
					tolerance: 'pointer',
					hoverClass: 'canDrop'
				});
			}

			this._resize();
		},

		/**
		 * Makes a breadcrumb structure based on the given path
		 *
		 * @param {String} dir path to split into a breadcrumb structure
		 * @return {Object.<String, String>} map of {dir: path, name: displayName}
		 */
		_makeCrumbs: function(dir) {
			var crumbs = [];
			var pathToHere = '';
			// trim leading and trailing slashes
			dir = dir.replace(/^\/+|\/+$/g, '');
			var parts = dir.split('/');
			if (dir === '') {
				parts = [];
			}
			// menu part
			crumbs.push({
				class: 'crumbmenu',
				linkclass: 'icon-more'
			});
			// root part
			crumbs.push({
				name: t('core', 'Home'),
				dir: '/',
				class: 'crumbhome',
				linkclass: 'icon-home'
			});
			for (var i = 0; i < parts.length; i++) {
				var part = parts[i];
				pathToHere = pathToHere + '/' + part;
				crumbs.push({
					dir: pathToHere,
					name: part
				});
			}
			return crumbs;
		},

 		/**
 		 * Hide the middle crumb
 		 */
 		_hideCrumb: function() {
			var selector = '.crumb:not(.hidden):not(.crumbhome):not(.crumbmenu)';
			var length = this.$el.find(selector).length;
			// Get the middle one floored down
			var elmt = Math.floor(length / 2 - 0.5);
			this.$el.find(selector+':eq('+elmt+')').addClass('hidden');
 		},

 		/**
 		 * Get the crumb to show
 		 */
 		_getCrumbElement: function() {
			var length = this.$el.find('.crumb.hidden').length;
			// Get the outer one with priority to the highest
			var elmt = (length % 2) * (length - 1);
			return this.$el.find('.crumb.hidden:eq('+elmt+')');
		},

 		/**
 		 * Show the middle crumb
 		 */
 		_showCrumb: function() {
			if(this.$el.find('.crumb.hidden').length === 1) {
				this.$el.find('.crumb.hidden').removeClass('hidden');
			}
			this._getCrumbElement().removeClass('hidden');
 		},

		/**
		 * Create and append the popovermenu
		 */
		_createMenu: function() {
			this.$el.find('.crumbmenu').append(this.$menu);
			this.$menu.children('ul').empty();
		},

		/**
		 * Update the popovermenu
		 */
		_updateMenu: function() {
			var menuItems = this.$el.children('.crumb.hidden');
			// Hide the crumb menu if no elements
			this.$el.find('.crumbmenu').toggleClass('hidden', menuItems.length===0)

			this.$menu.find('li').addClass('in-breadcrumb');
			for (var i = 0; i < menuItems.length; i++) {
				var crumbId = $(menuItems[i]).data('crumb-id');
				this.$menu.find('li:eq('+crumbId+')').removeClass('in-breadcrumb');
			}
		},

		_resize: function() {
			var i, $crumb, $ellipsisCrumb;

			if (!this.availableWidth) {
				this.availableWidth = this.$el.width();
			}

			if (this.breadcrumbs.length <= 1) {
				return;
			}

			// If container is smaller than content
			while (this.$el.width() > this.$el.parent().width()) {
				this._hideCrumb();
			}
			// If container is bigger than content + element to be shown
			// AND if there is at least one hidden crumb
			while (this.$el.find('.crumb.hidden').length > 0
				&& this.$el.width() + this._getCrumbElement().width() < this.$el.parent().width()) {
				this._showCrumb();
			}

			this._updateMenu();
		}
	};

	OCA.Files.BreadCrumb = BreadCrumb;
})();
