/**
 * Copyright (c) 2012 David Iwanowitsch <david at unclouded dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
$(document).ready(function(){
	OC.search.customResults['Bookm.'] = function(row,item){
		var a=row.find('a');
		a.attr('target','_blank');
		a.click(recordClick);
	}
});

function recordClick(event) {
	var jsFileLocation = $('script[src*=bookmarksearch]').attr('src');
	jsFileLocation = jsFileLocation.replace('js/bookmarksearch.js', '');
	$.ajax({
		type: 'POST',
		url: jsFileLocation + 'ajax/recordClick.php',
		data: 'url=' + encodeURI($(this).attr('href')),
	});	
}
