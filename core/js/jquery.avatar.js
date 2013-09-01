/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This plugins inserts the right avatar for the user, depending on, whether
 * he has a custom uploaded avatar, or not and show a placeholder with the
 * first letter of the users displayname instead.
 * For this it asks the core_avatar_get route, thus this plugin is fit very
 * tightly fitted for owncloud. It may not work anywhere else.
 *
 * You may use this on any <div></div>
 * Here I'm using <div class="avatardiv"></div> as an example.
 *
 * There are 3 ways to call this:
 *
 * 1. $('.avatardiv').avatar('jdoe', 128);
 * This will make the div to jdoe's fitting avatar, with the size of 128px.
 *
 * 2. $('.avatardiv').avatar('jdoe');
 * This will make the div to jdoe's fitting avatar. If the div aready has a
 * height, it will be used for the avatars size. Otherwise this plugin will
 * search for 'size' DOM data, to use it for avatar size. If neither are
 * available it will default to 64px.
 *
 * 3. $('.avatardiv').avatar();
 * This will search the DOM for 'user' data, to use as the username. If there
 * is no username available it will default to a placeholder with the value of
 * "x". The size will be determined the same way, as the second example did.
 */

(function ($) {
	$.fn.avatar = function(user, size) {
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
				this.placeholder('x');
				return;
			}
		}

		// sanitize
		user = user.replace(/\//g,'');

		var $div = this;

		OC.Router.registerLoadedCallback(function() {
			var url = OC.Router.generate('core_avatar_get', {user: user, size: size});
			$.get(url, function(result) {
				if (typeof(result) === 'object') {
					$div.placeholder(result.user);
				} else {
					$div.html('<img src="'+url+'">');
				}
			});
		});
	};
}(jQuery));
