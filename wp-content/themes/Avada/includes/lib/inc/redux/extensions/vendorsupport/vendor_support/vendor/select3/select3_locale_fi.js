/**
 * Select3 Finnish translation
 */
(function ($) {
    "use strict";
    $.fn.select3.locales['fi'] = {
        formatNoMatches: function () {
            return "Ei tuloksia";
        },
        formatInputTooShort: function (input, min) {
            var n = min - input.length;
            return "Ole hyvä ja anna " + n + " merkkiä lisää";
        },
        formatInputTooLong: function (input, max) {
            var n = input.length - max;
            return "Ole hyvä ja anna " + n + " merkkiä vähemmän";
        },
        formatSelectionTooBig: function (limit) {
            return "Voit valita ainoastaan " + limit + " kpl";
        },
        formatLoadMore: function (pageNumber) {
            return "Ladataan lisää tuloksia…";
        },
        formatSearching: function () {
            return "Etsitään…";
        }
    };

    $.extend($.fn.select3.defaults, $.fn.select3.locales['fi']);
})(jQuery);
