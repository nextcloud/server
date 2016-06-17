/**
 * @author Björn Schießle <schiessle@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

$(document).ready(function () {

	// show input field to add a new trusted server
	$("#ocFederationAddServer").on('click', function() {
		$('#ocFederationAddServerButton').addClass('hidden');
		$("#serverUrl").removeClass('hidden');
		$("#serverUrl").focus();
	});

	// add new trusted server
	$("#serverUrl").keyup(function (e) {
		if (e.keyCode === 13) { // add server on "enter"
			var url = $('#serverUrl').val();
			OC.msg.startSaving('#ocFederationAddServer .msg');
			$.post(
				OC.generateUrl('/apps/federation/trusted-servers'),
				{
					url: url
				}
			).done(function (data) {
					$('#serverUrl').attr('value', '');
					$('ul#listOfTrustedServers').prepend(
						$('<li>')
								.attr('id', data.id)
								.html('<span class="status indeterminate"></span>' +
									data.url +
									'<span class="icon icon-delete"></span>')
					);
					OC.msg.finishedSuccess('#ocFederationAddServer .msg', data.message);
				})
				.fail(function (jqXHR) {
					OC.msg.finishedError('#ocFederationAddServer .msg', JSON.parse(jqXHR.responseText).message);
				});
		} else if (e.keyCode === 27) { // hide input filed again in ESC
			$('#ocFederationAddServerButton').toggleClass('hidden');
			$("#serverUrl").toggleClass('hidden');
		}
	});

// remove trusted server from list
	$( "#listOfTrustedServers" ).on('click', 'li > .icon-delete', function() {
		var $this = $(this).parent();
		var id = $this.attr('id');
		$.ajax({
			url: OC.generateUrl('/apps/federation/trusted-servers/' + id),
			type: 'DELETE',
			success: function(response) {
				$this.remove();
			}
		});

	});

	$("#ocFederationSettings #autoAddServers").change(function() {
		$.post(
				OC.generateUrl('/apps/federation/auto-add-servers'),
				{
					autoAddServers: $(this).is(":checked")
				}
		);
	});

});
