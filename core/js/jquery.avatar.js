/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This plugin inserts the right avatar for the user, depending on, whether a
 * custom avatar is uploaded - which it uses then - or not, and display a
 * placeholder with the first letter of the users name instead.
 * For this it queries the core_avatar_get route, thus this plugin is fit very
 * tightly for owncloud, and it may not work anywhere else.
 *
 * You may use this on any <div></div>
 * Here I'm using <div class="avatardiv"></div> as an example.
 *
 * There are 5 ways to call this:
 *
 * 1. $('.avatardiv').avatar('jdoe', 128);
 * This will make the div to jdoe's fitting avatar, with a size of 128px.
 *
 * 2. $('.avatardiv').avatar('jdoe');
 * This will make the div to jdoe's fitting avatar. If the div already has a
 * height, it will be used for the avatars size. Otherwise this plugin will
 * search for 'size' DOM data, to use for avatar size. If neither are available
 * it will default to 64px.
 *
 * 3. $('.avatardiv').avatar();
 * This will search the DOM for 'user' data, to use as the username. If there
 * is no username available it will default to a placeholder with the value of
 * "x". The size will be determined the same way, as the second example.
 *
 * 4. $('.avatardiv').avatar('jdoe', 128, true);
 * This will behave like the first example, except it will also append random
 * hashes to the custom avatar images, to force image reloading in IE8.
 *
 * 5. $('.avatardiv').avatar('jdoe', 128, undefined, true);
 * This will behave like the first example, but it will hide the avatardiv, if
 * it will display the default placeholder. undefined is the ie8fix from
 * example 4 and can be either true, or false/undefined, to be ignored.
 *
 * 6. $('.avatardiv').avatar('jdoe', 128, undefined, true, callback);
 * This will behave like the above example, but it will call the function
 * defined in callback after the avatar is placed into the DOM.
 *
 */

(function ($) {
	$.fn.avatar = function(user, size, ie8fix, hidedefault, callback, displayname) {
		if (typeof(size) === 'undefined') {
			if (this.height() > 0) {
				size = this.height();
			} else if (this.data('size') > 0) {
				size = this.data('size');
			} else {
				size = 64;
			}
		}

		this.height(size);
		this.width(size);

		if (typeof(user) === 'undefined') {
			if (typeof(this.data('user')) !== 'undefined') {
				user = this.data('user');
			} else {
				this.imageplaceholder('x');
				return;
			}
		}

		// sanitize
		user = String(user).replace(/\//g,'');

		var $div = this;

		var url = OC.generateUrl(
			'/avatar/{user}/{size}',
			{user: user, size: Math.ceil(size * window.devicePixelRatio)});

		// If the displayname is not defined we use the old code path
		if (typeof(displayname) === 'undefined') {
			$.get(url).always(function(result, status) {
				// if there is an error or an object returned (contains user information):
				// -> show the fallback placeholder
				if (typeof(result) === 'object' || status === 'error') {
					if (!hidedefault) {
						if (result.data && result.data.displayname) {
							$div.imageplaceholder(user, result.data.displayname);
						} else {
							// User does not exist
							$div.imageplaceholder(user, 'X');
							$div.css('background-color', '#b9b9b9');
						}
					} else {
						$div.hide();
					}
				// else an image is transferred and should be shown
				} else {
					$div.show();
					if (ie8fix === true) {
						$div.html('<img width="' + size + '" height="' + size + '" src="'+url+'#'+Math.floor(Math.random()*1000)+'">');
					} else {
						$div.html('<img width="' + size + '" height="' + size + '" src="'+url+'">');
					}
				}
				if(typeof callback === 'function') {
					callback();
				}
			});
		} else {
			// We already have the displayname so set the placeholder (to show at least something)
			if (!hidedefault) {
				$div.imageplaceholder(displayname);
			}

			var img = new Image();

			// If the new image loads successfully set it.
			img.onload = function() {
				$div.show();
				$div.text('');
				$div.append(img);
			}

			img.width = size;
			img.height = size;
			img.src = url;
		}
	};
}(jQuery));
