/**
 * Select3 Macedonian translation.
 * 
 * Author: Marko Aleksic <psybaron@gmail.com>
 */
(function ($) {
    "use strict";

    $.fn.select3.locales['mk'] = {
        formatNoMatches: function () { return "Нема пронајдено совпаѓања"; },
        formatInputTooShort: function (input, min) { var n = min - input.length; return "Ве молиме внесете уште " + n + " карактер" + (n == 1 ? "" : "и"); },
        formatInputTooLong: function (input, max) { var n = input.length - max; return "Ве молиме внесете " + n + " помалку карактер" + (n == 1? "" : "и"); },
        formatSelectionTooBig: function (limit) { return "Можете да изберете само " + limit + " ставк" + (limit == 1 ? "а" : "и"); },
        formatLoadMore: function (pageNumber) { return "Вчитување резултати…"; },
        formatSearching: function () { return "Пребарување…"; }
    };

    $.extend($.fn.select3.defaults, $.fn.select3.locales['mk']);
})(jQuery);