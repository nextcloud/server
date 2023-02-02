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

(function( $ ) {

    // ocFederationAddServer
    $.fn.ocFederationAddServer = function() {

        /* Go easy on jquery and define some vars
        ========================================================================== */

        var $wrapper = $(this),

            // Buttons
            $btnAddServer   = $wrapper.find("#ocFederationAddServerButton"),
            $btnSubmit      = $wrapper.find("#ocFederationSubmit"),

            // Inputs
            $inpServerUrl   = $wrapper.find("#serverUrl"),

            // misc
            $msgBox         = $wrapper.find("#ocFederationAddServer .msg"),
            $srvList        = $wrapper.find("#listOfTrustedServers");


        /* Interaction
        ========================================================================== */

        $btnAddServer.on('click', function() {
            $btnAddServer.addClass('hidden');
            $inpServerUrl
                .removeClass('hidden')
                .focus();
        });

        // trigger server removal
        $srvList.on('click', 'li > .icon-delete', function() {
            var $this = $(this).parent();
            var id = $this.attr('id');

            removeServer( id );
        });

        $btnSubmit.on("click", function()
        {
            addServer($inpServerUrl.val());
        });

        $inpServerUrl.on("change keyup", function (e) {

            console.log("typing away");

            var url = $(this).val();

            // toggle add-button visibility based on input length
            if ( url.length > 0 )
                $btnSubmit.removeClass("hidden")
            else
                $btnSubmit.addClass("hidden")

            if (e.keyCode === 13) { // add server on "enter"
                addServer(url);
            } else if (e.keyCode === 27) { // hide input filed again in ESC
                $btnAddServer.removeClass('hidden');
                $inpServerUrl.val("").addClass('hidden');
                $btnSubmit.addClass('hidden');
            }
        });
    };

    /* private Functions
    ========================================================================== */

    function addServer( url ) {
        OC.msg.startSaving('#ocFederationAddServer .msg');

        $.post(
            OC.generateUrl('/apps/federation/trusted-servers'),
            {
                url: url
            }
        ).done(function (data) {
            $("#serverUrl").attr('value', '');
            $("#listOfTrustedServers").prepend(
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
    };

    function removeServer( id ) {
        $.ajax({
            url: OC.generateUrl('/apps/federation/trusted-servers/' + id),
            type: 'DELETE',
            success: function(response) {
                $("#ocFederationSettings").find("#" + id).remove();
            }
        });
    }


})( jQuery );

window.addEventListener('DOMContentLoaded', function () {

    $('#ocFederationSettings').ocFederationAddServer();

});
