/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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

		var url = OC.router_base_url+'/avatar/'+user+'/'+size // FIXME routes aren't loaded yet, so OC.Router.generate() doesn't work
		$.get(url, function(result) {
			if (typeof(result) === 'object') {
				$div.placeholder(result.user);
			} else {
				$div.html('<img src="'+url+'">');
			}
		});
	};
}(jQuery));
