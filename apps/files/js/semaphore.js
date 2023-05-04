/*
 * Copyright (c) 2018
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
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
