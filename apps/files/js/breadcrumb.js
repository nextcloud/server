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

/* global OC */
/* global SVGSupport, replaceSVG */
(function() {
	/**
	 * Creates an breadcrumb element in the given container
	 */
	var BreadCrumb = function(options){
		this.$el = $('<div class="breadcrumb"></div>');
		options = options || {};
		if (options.onClick) {
			this.onClick = options.onClick;
		}
		if (options.onDrop) {
			this.onDrop = options.onDrop;
		}
		if (options.getCrumbUrl) {
			this.getCrumbUrl = options.getCrumbUrl;
		}
	};
	BreadCrumb.prototype = {
		$el: null,
		dir: null,

		lastWidth: 0,
		hiddenBreadcrumbs: 0,
		totalWidth: 0,
		breadcrumbs: [],
		onClick: null,
		onDrop: null,

		/**
		 * Sets the directory to be displayed as breadcrumb.
		 * This will re-render the breadcrumb.
		 * @param dir path to be displayed as breadcrumb
		 */
		setDirectory: function(dir) {
			dir = dir || '/';
			if (dir !== this.dir) {
				this.dir = dir;
				this.render();
			}
		},

		/**
		 * Returns the full URL to the given directory
		 * @param part crumb data as map
		 * @param index crumb index
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
				$crumb = $('<div class="crumb"></div>');
				$crumb.append($link);
				$crumb.attr('data-dir', part.dir);

				if (part.img) {
					$image = $('<img class="svg"></img>');
					$image.attr('src', part.img);
					$link.append($image);
				}
				this.breadcrumbs.push($crumb);
				this.$el.append($crumb);
				if (this.onClick) {
					$crumb.on('click', this.onClick);
				}
			}
			$crumb.addClass('last');

			// in case svg is not supported by the browser we need to execute the fallback mechanism
			if (!SVGSupport()) {
				replaceSVG();
			}

			// setup drag and drop
			if (this.onDrop) {
				this.$el.find('.crumb:not(.last)').droppable({
					drop: this.onDrop,
					tolerance: 'pointer'
				});
			}

			this._updateTotalWidth();
			this.resize($(window).width(), true);
		},

		/**
		 * Makes a breadcrumb structure based on the given path
		 * @param dir path to split into a breadcrumb structure
		 * @return array of map {dir: path, name: displayName}
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

		_updateTotalWidth: function () {
			var self = this;

			this.lastWidth = 0;

			// initialize with some extra space
			this.totalWidth = 64;
			// FIXME: this class should not know about global elements
			if ( $('#navigation').length ) {
				this.totalWidth += $('#navigation').get(0).offsetWidth;
			}
			this.hiddenBreadcrumbs = 0;

			for (var i = 0; i < this.breadcrumbs.length; i++ ) {
				this.totalWidth += $(this.breadcrumbs[i]).get(0).offsetWidth;
			}

			$.each($('#controls .actions>div'), function(index, action) {
				self.totalWidth += $(action).get(0).offsetWidth;
			});

		},

		/**
		 * Show/hide breadcrumbs to fit the given width
		 */
		resize: function (width, firstRun) {
			var i, $crumb;

			if (width === this.lastWidth) {
				return;
			}

			// window was shrinked since last time or first run ?
			if ((width < this.lastWidth || firstRun) && width < this.totalWidth) {
				if (this.hiddenBreadcrumbs === 0 && this.breadcrumbs.length > 1) {
					// start by hiding the first breadcrumb after home,
					// that one will have extra three dots displayed
					$crumb = this.breadcrumbs[1];
					this.totalWidth -= $crumb.get(0).offsetWidth;
					$crumb.find('a').addClass('hidden');
					$crumb.append('<span class="ellipsis">...</span>');
					this.totalWidth += $crumb.get(0).offsetWidth;
					this.hiddenBreadcrumbs = 2;
				}
				i = this.hiddenBreadcrumbs;
				// hide subsequent breadcrumbs if the space is still not enough
				while (width < this.totalWidth && i > 1 && i < this.breadcrumbs.length - 1) {
					$crumb = this.breadcrumbs[i];
					this.totalWidth -= $crumb.get(0).offsetWidth;
					$crumb.addClass('hidden');
					this.hiddenBreadcrumbs = i;
					i++;
				}
			// window is bigger than last time
			} else if (width > this.lastWidth && this.hiddenBreadcrumbs > 0) {
				i = this.hiddenBreadcrumbs;
				while (width > this.totalWidth && i > 0) {
					if (this.hiddenBreadcrumbs === 1) {
						// special handling for last one as it has the three dots
						$crumb = this.breadcrumbs[1];
						if ($crumb) {
							this.totalWidth -= $crumb.get(0).offsetWidth;
							$crumb.find('.ellipsis').remove();
							$crumb.find('a').removeClass('hidden');
							this.totalWidth += $crumb.get(0).offsetWidth;
						}
					} else {
						$crumb = this.breadcrumbs[i];
						$crumb.removeClass('hidden');
						this.totalWidth += $crumb.get(0).offsetWidth;
						if (this.totalWidth > width) {
							this.totalWidth -= $crumb.get(0).offsetWidth;
							$crumb.addClass('hidden');
							break;
						}
					}
					i--;
					this.hiddenBreadcrumbs = i;
				}
			}

			this.lastWidth = width;
		}
	};

	window.BreadCrumb = BreadCrumb;
})();

