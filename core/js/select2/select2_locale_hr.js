/**
 * Select2 Croatian translation.
 *
 * @author  Edi Modrić <edi.modric@gmail.com>
 * @author  Uriy Efremochkin <efremochkin@uriy.me>
 */
(function ($) {
    "use strict";

    $.extend($.fn.select2.defaults, {
        formatNoMatches: function () { return "Nema rezultata"; },
        formatInputTooShort: function (input, min) { return "Unesite još" + character(min - input.length); },
        formatInputTooLong: function (input, max) { return "Unesite" + character(input.length - max) + " manje"; },
        formatSelectionTooBig: function (limit) { return "Maksimalan broj odabranih stavki je " + limit; },
        formatLoadMore: function (pageNumber) { return "Učitavanje rezultata…"; },
        formatSearching: function () { return "Pretraga…"; }
    });

    function character (n) {
        return " " + n + " znak" + (n%10 < 5 && n%10 > 0 && (n%100 < 5 || n%100 > 19) ? n%10 > 1 ? "a" : "" : "ova");
    }
})(jQuery);
