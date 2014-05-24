/**
 * ownCloud - core
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2014
 */
(function (document, $, exports) {

    'use strict';

    var buttons = $();

    /**
     * Allow apps to register buttons at runtime to not impact performance
     * negatively on document click
     * @param $ button wrapped in jquery result
     */
    exports.Apps = {
        registerSlideToggleButton: function (button) {
            buttons = buttons.add(button);
        }
    };


    /**
     * Provides a way to slide down a target area through a button and slide it
     * up if the user clicks somewhere else. Used for the news app settings and
     * add new field.
     *
     * Usage:
     * <button data-apps-slide-toggle=".slide-area">slide</button>
     * <div class=".slide-area" class="hidden">I'm sliding up</div>
     */
    var registerAppsSlideToggle = function () {
        // use only buttons that are already in the dom
        buttons = buttons.add($('[data-apps-slide-toggle]'));

        $(document).click(function (event) {

            buttons.each(function (index, button) {

                var areaSelector = $(button).data('apps-slide-toggle');
                var area = $(areaSelector);

                // do nothing if the area is animated
                if (!area.is(':animated')) {

                    // button toggles the area
                    if (button === event.target) {
                        if (area.is(':visible')) {
                            area.slideUp();
                        } else {
                            area.slideDown();
                        }

                    // all other areas that have not been clicked but are open
                    // should be slid up
                    } else {
                        var closest = $(event.target).closest(areaSelector);
                        if (area.is(':visible') && closest[0] !== area[0]) {
                            area.slideUp();
                        }
                    }
                }
            });

        });
    };


    $(document).ready(function () {
        registerAppsSlideToggle();
    });

}(document, jQuery, OC));