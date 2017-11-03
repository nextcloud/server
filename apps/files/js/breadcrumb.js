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
			this.$el.empty();
			this.breadcrumbs = [];

			for (var i = 0; i < parts.length; i++) {
				var part = parts[i];
				var $image;
				var $link = $('<a></a>').attr('href', this.getCrumbUrl(part, i));
				$link.text(part.name);
				$crumb = $('<div class="crumb svg"></div>');
				$crumb.append($link);
				$crumb.attr('data-dir', part.dir);

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
			$crumb.addClass('last');

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

			this._updateTotalWidth();
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
			// root part
			crumbs.push({
				dir: '/',
				name: '',
				alt: t('files', 'Home'),
				img: OC.imagePath('core', 'places/home.svg')
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
		 * Calculate the total breadcrumb width when
		 * all crumbs are expanded
		 */
		_updateTotalWidth: function () {
			this.totalWidth = 0;
			for (var i = 0; i < this.breadcrumbs.length; i++ ) {
				var $crumb = $(this.breadcrumbs[i]);
				this.totalWidth += $crumb.width();
			}
			this._resize();
		},

		/**
		 * Show/hide breadcrumbs to fit the given width
		 *
		 * @param {int} availableWidth available width
		 */
		setMaxWidth: function (availableWidth) {
			if (this.availableWidth !== availableWidth) {
				this.availableWidth = availableWidth;
				this._resize();
			}
		},

		/**
		 * Return the number of items to hide
		 */
		_toShrink: function() {
			var maxWidth = this.$el.width();
			var smallestWidth = 50;
			// 50px by default for the ellipsis crumb
			return Math.ceil((this.totalWidth + 50 - maxWidth) / smallestWidth);
		},

		/**
		 * Hide the desired number of items
		 *
		 * @param {int} number to hide
		 */
		 _hideCrumbs: function(toHide) {
			 var min = Math.round(this.breadcrumbs.length/2 - toHide/2);
			 var max = Math.round(this.breadcrumbs.length/2 + toHide/2 - 1);
			 console.log(this.$el.find('.crumb').slice(min, max));
			 this.$el.find('.crumb').removeClass('hidden')
			 	.slice(min, max).addClass('hidden');
		 },

		_resize: function() {
			var i, $crumb, $ellipsisCrumb;

			if (!this.availableWidth) {
				this.availableWidth = this.$el.width();
			}

			if (this.breadcrumbs.length <= 1) {
				return;
			}
			this._hideCrumbs(this._toShrink());



		}
	};

	OCA.Files.BreadCrumb = BreadCrumb;
})();
