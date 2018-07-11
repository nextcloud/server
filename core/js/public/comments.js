/**
 * @copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 */

(function(OCP) {
	"use strict";

	OCP.Comments = {

		/*
		 * Detects links:
		 * Either the http(s) protocol is given or two strings, basically limited to ascii with the last
		 * 	word being at least one digit long,
		 * followed by at least another character
		 *
		 * The downside: anything not ascii is excluded. Not sure how common it is in areas using different
		 * alphabets… the upside: fake domains with similar looking characters won't be formatted as links
		 */
		urlRegex: /(\s|^)(https?:\/\/)?((?:[-A-Z0-9+_]*\.)+[-A-Z]+(?:\/[-A-Z0-9+&@#%?=~_|!:,.;()]*)*)(\s|$)/ig,

		plainToRich: function(content) {
			content = this.formatLinksRich(content);
			return content;
		},

		richToPlain: function(content) {
			content = this.formatLinksPlain(content);
			return content;
		},

		formatLinksRich: function(content) {
			return content.replace(this.urlRegex, function(_, leadingSpace, protocol, url, trailingSpace) {
				var linkText = url;
				if(!protocol) {
					protocol = 'https://';
				} else if (protocol === 'http://'){
					linkText = protocol + url;
				}

				return leadingSpace + '<a class="external" target="_blank" rel="noopener noreferrer" href="' + protocol + url + '">' + linkText + '</a>' + trailingSpace;
			});
		},

		formatLinksPlain: function(content) {
			var $content = $('<div></div>').html(content);
			$content.find('a').each(function () {
				var $this = $(this);
				$this.html($this.attr('href'));
			});
			return $content.html();
		}

	};
})(OCP);
