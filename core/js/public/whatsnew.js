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

	OCP.WhatsNew = {

		query: function(options) {
			options = options || {};
			var dismissOptions = options.dismiss || {};
			$.ajax({
				type: 'GET',
				url: options.url || OC.linkToOCS('core', 2) + 'whatsnew?format=json',
				success: options.success || function(data, statusText, xhr) {
					OCP.WhatsNew._onQuerySuccess(data, statusText, xhr, dismissOptions);
				},
				error: options.error || this._onQueryError
			});
		},

		dismiss: function(version, options) {
			options = options || {};
			$.ajax({
				type: 'POST',
				url: options.url || OC.linkToOCS('core', 2) + 'whatsnew',
				data: {version: encodeURIComponent(version)},
				success: options.success || this._onDismissSuccess,
				error: options.error || this._onDismissError
			});
			// remove element immediately
			$('.whatsNewPopover').remove();
		},

		_onQuerySuccess: function(data, statusText, xhr, dismissOptions) {
			console.debug('querying Whats New data was successful: ' + statusText);
			console.debug(data);

			if(xhr.status !== 200) {
				return;
			}

			var item, menuItem, text, icon;

			var div = document.createElement('div');
			div.classList.add('popovermenu', 'open', 'whatsNewPopover', 'menu-left');

			var list = document.createElement('ul');

			// header
			item = document.createElement('li');
			menuItem = document.createElement('span');
			menuItem.className = "menuitem";

			text = document.createElement('span');
			text.innerText = t('core', 'New in') + ' ' + data['ocs']['data']['product'];
			text.className = 'caption';
			menuItem.appendChild(text);

			icon = document.createElement('span');
			icon.className = 'icon-close';
			icon.onclick = function () {
				OCP.WhatsNew.dismiss(data['ocs']['data']['version'], dismissOptions);
			};
			menuItem.appendChild(icon);

			item.appendChild(menuItem);
			list.appendChild(item);

			// Highlights
			for (var i in data['ocs']['data']['whatsNew']['regular']) {
				var whatsNewTextItem = data['ocs']['data']['whatsNew']['regular'][i];
				item = document.createElement('li');

				menuItem = document.createElement('span');
				menuItem.className = "menuitem";

				icon = document.createElement('span');
				icon.className = 'icon-checkmark';
				menuItem.appendChild(icon);

				text = document.createElement('p');
				text.innerHTML = _.escape(whatsNewTextItem);
				menuItem.appendChild(text);

				item.appendChild(menuItem);
				list.appendChild(item);
			}

			// Changelog URL
			if(!_.isUndefined(data['ocs']['data']['changelogURL'])) {
				item = document.createElement('li');

				menuItem = document.createElement('a');
				menuItem.href = data['ocs']['data']['changelogURL'];
				menuItem.rel = 'noreferrer noopener';
				menuItem.target = '_blank';

				icon = document.createElement('span');
				icon.className = 'icon-link';
				menuItem.appendChild(icon);

				text = document.createElement('span');
				text.innerText = t('core', 'View changelog');
				menuItem.appendChild(text);

				item.appendChild(menuItem);
				list.appendChild(item);
			}

			div.appendChild(list);
			document.body.appendChild(div);
		},

		_onQueryError: function (x, t, e) {
			console.debug('querying Whats New Data resulted in an error: ' + t + e);
			console.debug(x);
		},

		_onDismissSuccess: function(data) {
			//noop
		},

		_onDismissError: function (data) {
			console.debug('dismissing Whats New data resulted in an error: ' + data);
		}
	};
})(OCP);
