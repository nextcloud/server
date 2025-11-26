/*!
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

/**
 * @param $ - The jQuery instance
 */
(function($) {
	// ocFederationAddServer
	$.fn.ocFederationAddServer = function() {
		/* Go easy on jquery and define some vars
        ========================================================================== */

		const $wrapper = $(this),

			// Buttons
			$btnAddServer = $wrapper.find('#ocFederationAddServerButton'),
			$btnSubmit = $wrapper.find('#ocFederationSubmit'),

			// Inputs
			$inpServerUrl = $wrapper.find('#serverUrl'),

			// misc
			$msgBox = $wrapper.find('#ocFederationAddServer .msg'),
			$srvList = $wrapper.find('#listOfTrustedServers')

		/* Interaction
        ========================================================================== */

		$btnAddServer.on('click', function() {
			$btnAddServer.addClass('hidden')
			$wrapper.find('.serverUrl').removeClass('hidden')
			$inpServerUrl
				.focus()
		})

		// trigger server removal
		$srvList.on('click', 'li > .icon-delete', function() {
			const $this = $(this).parent()
			const id = $this.attr('id')

			removeServer(id)
		})

		$btnSubmit.on('click', function() {
			addServer($inpServerUrl.val())
		})

		$inpServerUrl.on('change keyup', function(e) {
			const url = $(this).val()

			// toggle add-button visibility based on input length
			if (url.length > 0) { $btnSubmit.removeClass('hidden') } else { $btnSubmit.addClass('hidden') }

			if (e.keyCode === 13) { // add server on "enter"
				addServer(url)
			} else if (e.keyCode === 27) { // hide input filed again in ESC
				$btnAddServer.removeClass('hidden')
				$inpServerUrl.val('').addClass('hidden')
				$btnSubmit.addClass('hidden')
			}
		})
	}

	/* private Functions
    ========================================================================== */

	/**
	 *
	 * @param url
	 */
	function addServer(url) {
		OC.msg.startSaving('#ocFederationAddServer .msg')

		$.post(
			OC.getRootPath() + '/ocs/v2.php/apps/federation/trusted-servers',
			{
				url,
			},
			null,
			'json',
		).done(function({ ocs }) {
			const data = ocs.data
			$('#serverUrl').attr('value', '')
			$('#listOfTrustedServers').prepend($('<li>')
				.attr('id', data.id)
				.html('<span class="status indeterminate"></span>'
					+ data.url
					+ '<span class="icon icon-delete"></span>'))
			OC.msg.finishedSuccess('#ocFederationAddServer .msg', data.message)
		})
			.fail(function(jqXHR) {
				OC.msg.finishedError('#ocFederationAddServer .msg', JSON.parse(jqXHR.responseText).ocs.meta.message)
			})
	}

	/**
	 *
	 * @param id
	 */
	function removeServer(id) {
		$.ajax({
			url: OC.getRootPath() + '/ocs/v2.php/apps/federation/trusted-servers/' + id,
			type: 'DELETE',
			success: function(response) {
				$('#ocFederationSettings').find('#' + id).remove()
			},
		})
	}
})(jQuery)

window.addEventListener('DOMContentLoaded', function() {
	$('#ocFederationSettings').ocFederationAddServer()
})
