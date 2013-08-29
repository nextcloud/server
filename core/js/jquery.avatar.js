/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

(function ($) {
	$.fn.avatar = function(user, height) {
		// TODO there has to be a better way …
		if (typeof(height) === 'undefined') {
			height = this.height();
		}
		if (height === 0) {
			height = 64;
		}

		this.height(height);
		this.width(height);

		if (typeof(user) === 'undefined') {
			this.placeholder('x');
			return;
		}

		var $div = this;

		//$.get(OC.Router.generate('core_avatar_get', {user: user, size: height}), function(result) { // TODO does not work "Uncaught TypeError: Cannot use 'in' operator to search for 'core_avatar_get' in undefined" router.js L22
		$.get(OC.router_base_url+'/avatar/'+user+'/'+height, function(result) {
			if (typeof(result) === 'object') {
				$div.placeholder(result.user);
			} else {
				$div.html('<img src="'+OC.Router.generate('core_avatar_get', {user: user, size: height})+'">');
			}
		});
        };
}(jQuery));
