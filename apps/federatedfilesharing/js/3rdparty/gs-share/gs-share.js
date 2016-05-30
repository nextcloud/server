/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

// @license magnet:?xt=urn:btih:3877d6d54b3accd4bc32f8a48bf32ebc0901502a&dn=mpl-2.0.txt MPL 2.0
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    /**
     * 'Share' widget for GNU social
     * http://code.chromic.org/project/view/2/
     *
     * We make a few assumptions about the target instance:
     *   1) The API root is in the default location
     *   2) Fancy URLs are enabled
     *   3) CORS is allowed
     *   4) The Bookmark plugin is enabled
     *
     * If 1), 3) or 4) are wrong, we fall back to a regular
     * notice (instead of a bookmark notice)
     *
     * If 2) is wrong the user will be directed to a 404 :(
     */

    // TODO: input sanitation [1], [2]
    // TODO: server-side fallback if JS is disabled

    var createForm,
        bindClicks,
        frm,
        shareAsNotice,
        shareAsBookmark,
        extractURLParams,
        shareURL,
        shareTitle,
        closest,
        i18n = window.i18n;

    /**
     * Internationalization
     */
    if (i18n === undefined) {
        i18n = {
            invalidId: 'The account id provided is invalid',
            yourAcctId: 'Your account ID:',
            idPlaceholder: 'user@example.org',
            shareAsBookmark: 'Share as a bookmark'
        };
    }

    shareAsNotice = function (title, url, domain) {
        window.open('http://' + domain + '/notice/new?status_textarea=' + title + ' ' + url); // [1]
    };

    shareAsBookmark = function (title, url, domain) {
        window.open('http://' + domain + '/main/bookmark/new?url=' + url + '&title=' + title); // [2]
    };

    /**
     * Extract parameters from query string
     *
     * ex:
     *   ?foo=bar&baz=test
     * will return:
     *   {foo: 'bar', baz: 'test'}
     */
    extractURLParams = function (queryStr) {
        var parts = queryStr.substr(1).split('&'),
            i, len, keyVal, params = {};

        for (i = 0, len = parts.length; i < len; i += 1) {
            keyVal = parts[i].split('=');
            params[keyVal[0]] = keyVal[1];
        }

        return params;
    };

    // Create the form that we'll re-use throughout the page
    createForm = function () {
        var err = document.createElement('div');
        err.setAttribute('class', 'gs-share-err');
        err.setAttribute('tabindex', '-1');
        err.setAttribute('aria-hidden', 'true');
        err.textContent = i18n.invalidId;

        frm = document.createElement('form');

        frm.setAttribute('class', 'gs-share-form');
        frm.setAttribute('tabindex', '-1');
        frm.setAttribute('aria-hidden', 'true');

        frm.innerHTML = '<label for="gs-account">' + i18n.yourAcctId + '</label>' +
            '<input type="text" id="gs-account" placeholder="' + i18n.idPlaceholder + '" />' +
            '<input type="checkbox" id="gs-bookmark" /> <label for="gs-bookmark">' + i18n.shareAsBookmark + '</label>' +
            '<input type="submit" value="Share"/>';
        frm.insertBefore(err, frm.firstChild);

        // Submit handler
        frm.addEventListener('submit', function (e) {
            e.preventDefault();

            var accountParts = document.getElementById('gs-account').value.split('@'),
                username, domain, xhr, bookmarkURL;

            if (accountParts.length === 2) {
                err.setAttribute('aria-hidden', 'true');

                username = accountParts[0];
                domain = accountParts[1];
                bookmarkURL = 'http://' + domain + '/api/bookmarks/' + username + '.json';

                // Try bookmark
                if (document.getElementById('gs-bookmark').checked) {
                    xhr = new XMLHttpRequest();

                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) { // Success
                                shareAsBookmark(shareTitle, shareURL, domain);
                            } else { // Failure, fallback to regular notice
                                shareAsNotice(shareTitle, shareURL, domain);
                            }
                        }
                    };

                    xhr.open('GET', bookmarkURL, true);
                    xhr.send();
                } else { // Regular notice
                    shareAsNotice(shareTitle, shareURL, domain);
                }
            } else { // Invalid account id
                err.setAttribute('aria-hidden', 'false');
                err.focus();
            }
        });

        // Keydown handler
        frm.addEventListener('keydown', function (e) {
            if (e.keyCode === 27) { // Escape key closes the dialog
                frm.parentElement.getElementsByClassName('js-gs-share')[0].focus();
                frm.setAttribute('aria-hidden', 'true');
            }
        });

        document.body.appendChild(frm);
    };

    /**
     * Something similar to jQuery.closest
     *
     * Given `elm`, return the closest parent with class `cls`
     * or false if there is no matching ancestor.
     */
    closest = function (elm, cls) {
        while (elm !== document) {
            if (elm.classList.contains(cls)) {
                return elm;
            }

            elm = elm.parentNode;
        }

        return false;
    };

    bindClicks = function () {
        document.addEventListener('click', function (e) {
            var target = e.target,
                urlParams,
                lnk = closest(target, 'js-gs-share');

            // Don't do anything on right/middle click or if ctrl or shift was pressed while left-clicking
            if (!e.button && !e.ctrlKey && !e.shiftKey && lnk) {
                e.preventDefault();

                // Check for submission information in href first
                if (lnk.search !== undefined) {
                    urlParams = extractURLParams(lnk.search);
                    shareURL   = urlParams.url;
                    shareTitle = urlParams.title;
                } else { // If it's not there, try data-* attributes. If not, use current document url and title
                    shareURL   = lnk.getAttribute('data-url') || window.location.href;
                    shareTitle = lnk.getAttribute('data-title') || document.title;
                }

                // Move form after the clicked link
                lnk.parentNode.appendChild(frm);

                // Show form
				frm.setAttribute('aria-hidden', 'false');

                // Focus on form
                frm.focus();
            } else if (!frm.contains(target)) {
                frm.setAttribute('aria-hidden', 'true');
            }
        });
    };

    // Flag that js is enabled
    document.body.classList.add('js-gs-share-enabled');

    createForm();
    bindClicks();
});
// @license-end
