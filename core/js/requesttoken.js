/**
 * ownCloud
 *
 * @file core/js/requesttoken.js
 * @brief Routine to refresh the Request protection request token periodically
 * @author Christian Reiner (arkascha)
 * @copyright 2011-2012 Christian Reiner <foss@christian-reiner.info>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the license, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 */

OC.Request = {
	// the request token
	Token: {},
	// the lifespan span (in secs)
	Lifespan: {},
	// method to refresh the local request token periodically
	Refresh: function(){
		// just a client side console log to preserve efficiency
		console.log("refreshing request token (lifebeat)");
		var dfd=new $.Deferred();
		$.ajax({
			type:     'POST',
			url:      OC.filePath('core','ajax','requesttoken.php'),
			cache:    false,
			data:     { },
			dataType: 'json'
		}).done(function(response){
			// store refreshed token inside this class
			OC.Request.Token=response.token;
			dfd.resolve();
		}).fail(dfd.reject);
		return dfd;
	}
}
// accept requesttoken and lifespan into the OC namespace
OC.Request.Token = oc_requesttoken;
OC.Request.Lifespan = oc_requestlifespan;
// refresh the request token periodically shortly before it becomes invalid on the server side
setInterval(OC.Request.Refresh,Math.floor(1000*OC.Request.Lifespan*0.93)), // 93% of lifespan value, close to when the token expires
// early bind token as additional ajax argument for every single request
$(document).bind('ajaxSend', function(elm, xhr, s){xhr.setRequestHeader('requesttoken', OC.Request.Token);});
