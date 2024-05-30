/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

(function(){
	var Semaphore = function(max) {
		var counter = 0;
		var waiting = [];

		this.acquire = function() {
			if(counter < max) {
				counter++;
				return new Promise(function(resolve) { resolve(); });
			} else {
				return new Promise(function(resolve) { waiting.push(resolve); });
			}
		};

		this.release = function() {
			counter--;
			if (waiting.length > 0 && counter < max) {
				counter++;
				var promise = waiting.shift();
				promise();
			}
		};
	};

	// needed on public share page to properly register this
	if (!OCA.Files) {
		OCA.Files = {};
	}
	OCA.Files.Semaphore = Semaphore;

})();
