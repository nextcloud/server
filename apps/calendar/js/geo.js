/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
if (navigator.geolocation) { 
	navigator.geolocation.getCurrentPosition(function(position) {
		$.getJSON(OC.filePath('calendar', 'ajax/settings', 'guesstimezone.php') + '?lat=' + position.coords.latitude + '&long=' + position.coords.longitude,
		function(data){
			if (data.status == 'success' && typeof(data.message) != 'undefined'){
				$('#notification').html(data.message);
				$('#notification').slideDown();
				window.setTimeout(function(){$('#notification').slideUp();}, 5000);
			}else{
				console.log('Can\'t set new timezone.');
			}
		});
	});
}