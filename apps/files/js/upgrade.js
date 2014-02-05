/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global OC */
$(document).ready(function () {
	var eventSource, total, bar = $('#progressbar');
	console.log('start');
	bar.progressbar({value: 0});
	eventSource = new OC.EventSource(OC.filePath('files', 'ajax', 'upgrade.php'));
	eventSource.listen('total', function (count) {
		total = count;
		console.log(count + ' files needed to be migrated');
	});
	eventSource.listen('count', function (count) {
		bar.progressbar({value: (count / total) * 100});
		console.log(count);
	});
	eventSource.listen('done', function () {
		document.location.reload();
	});
});
