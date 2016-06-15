/**
 * Strengthify - show the weakness of a password (uses zxcvbn for this)
 * https://github.com/MorrisJobke/strengthify
 *
 * Version: 0.5.1
 * Author: Morris Jobke (github.com/MorrisJobke) - original
 *         Eve Ragins @ Eve Corp (github.com/eve-corp)
 *
 *
 * License:
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2013-2016 Morris Jobke <morris.jobke@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/* global jQuery */
(function($) {
    $.fn.strengthify = function(paramOptions) {
        "use strict";

        var defaults = {
            zxcvbn: 'zxcvbn/zxcvbn.js',
            titles: [
                'Weakest',
                'Weak',
                'So-so',
                'Good',
                'Perfect'
            ],
            tilesOptions:{
              tooltip: true,
              element: false
            },
            drawTitles: false,
            drawMessage: false,
            drawBars: true
        };

        return this.each(function() {
            var options = $.extend(defaults, paramOptions);

            if (!options.drawTitles
                && !options.drawMessage
                && !options.drawBars)
                console.warn("expect at least one of 'drawTitles', 'drawMessage', or 'drawBars' to be true");

            function getWrapperFor(id) {
                return $('div[data-strengthifyFor="' + id + '"]');
            };

            function drawStrengthify() {
                var password = $(this).val(),
                    elemId = $(this).attr('id'),
                    // hide strengthify if no input is provided
                    opacity = (password === '') ? 0 : 1,
                    // calculate result
                    result = zxcvbn(password),
                    // setup some vars for later
                    css = '',
                    bsLevel = '',
                    message = '',
                    // cache jQuery selections
                    $wrapper = getWrapperFor(elemId),
                    $container = $wrapper.find('.strengthify-container'),
                    $message = $wrapper.find('[data-strengthifyMessage]');


                $wrapper.children()
                    .css('opacity', opacity)
                    .css('-ms-filter',
                    '"progid:DXImageTransform.Microsoft.Alpha(Opacity=' + opacity * 100 + ')"'
                    );

                // style strengthify bar
                // possible scores: 0-4
                switch (result.score) {
                    case 0:
                    case 1:
                        css = 'password-bad';
                        bsLevel = 'danger';
                        message = result.feedback ? result.feedback.suggestions.join('<br/>') : "";
                        break;
                    case 2:
                        bsLevel = 'warning';
                        message = result.feedback ? result.feedback.suggestions.join('<br/>') : "";
                        css = 'password-medium';
                        break;
                    case 3:
                        css = 'password-good';
                        bsLevel = 'info';
                        message = "Getting better.";
                    case 4:
                        css = 'password-good';
                        bsLevel = 'success';
                        message = "Looks good.";
                        break;
                }

                if ($message) {
                    $message.removeAttr('class');
                    $message.addClass('bg-' + bsLevel);

                    // reset state for empty string password
                    if (password === '') {
                        message = '';
                    }
                    $message.html(message);
                }
                if ($container) {
                    $container
                        .attr('class', css + ' strengthify-container')
                        // possible scores: 0-4
                        .css(
                        'width',
                        // if score is '0' it will be changed to '1' to
                        // not hide strengthify if the password is extremely weak
                        ((result.score === 0 ? 1 : result.score) * 25) + '%'
                        );

                    // reset state for empty string password
                    if (password === '') {
                        $container.css('width', 0);
                    }
                }

                if (options.drawTitles) {
                    // set a title for the wrapper
                    if(options.tilesOptions.tooltip){
                        $wrapper.attr(
                            'title',
                            options.titles[result.score]
                        ).tooltip({
                            placement: 'bottom',
                            trigger: 'manual',
                        }).tooltip(
                            'fixTitle'
                        ).tooltip(
                            'show'
                        );

                        if (opacity === 0) {
                            $wrapper.tooltip(
                                'hide'
                            );
                        }
                    }

                    if(options.tilesOptions.element){
                        $wrapper.find(".strengthify-tiles").text(options.titles[result.score]);
                    }
                }
            };

            function init() {
                var $elem = $(this),
                    elemId = $elem.attr('id');
                var drawSelf = drawStrengthify.bind(this);

                // add elements
                $elem.after('<div class="strengthify-wrapper" data-strengthifyFor="' + $elem.attr('id') + '"></div>');

                if (options.drawBars) {
                    getWrapperFor(elemId)
                        .append('<div class="strengthify-bg" />')
                        .append('<div class="strengthify-container" />')
                        .append('<div class="strengthify-separator" style="left: 25%" />')
                        .append('<div class="strengthify-separator" style="left: 50%" />')
                        .append('<div class="strengthify-separator" style="left: 75%" />');
                }

                if (options.drawMessage) {
                    getWrapperFor(elemId).append('<div data-strengthifyMessage></div>');
                }

                if (options.drawTitles && options.tilesOptions) {
                    getWrapperFor(elemId).append('<div class="strengthify-tiles"></div>');
                }

                $elem.parent().on('scroll', drawSelf);

                $.ajax({
                    cache: true,
                    dataType: 'script',
                    url: options.zxcvbn
                }).done(function() {
                    $elem.bind('keyup input change', drawSelf);
                });
            };

            init.call(this);

            //return me;
        });
    };

} (jQuery));
