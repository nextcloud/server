/**
 * Copyright (c) 2015
 *  Vincent Petry <pvince81@owncloud.com>
 *  Jan-Christoph Borchardt, http://jancborchardt.net
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/**
 * @namespace
 * @memberOf OC
 */
OC.Login = _.extend(OC.Login || {}, {
	onLogin: function () {
		$('#submit')
			.removeClass('icon-confirm')
			.addClass('icon-loading-small')
			.css('opacity', '1');
		return true;
	},

	rememberLogin: function(){
		if($(this).is(":checked")){
	    	if($("#user").val() && $("#password").val()) {
	     	 	$('#submit').trigger('click');
	    	} 
        }
	}
});

$(document).ready(function() {
	$('form[name=login]').submit(OC.Login.onLogin);

	$('#remember_login').click(OC.Login.rememberLogin);
});
