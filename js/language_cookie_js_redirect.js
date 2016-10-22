(function($, Drupal) {
    Drupal.behaviors.language_cookie_js_redirect = {
        attach: function(context) {
            var cookieName = drupalSettings.language_cookie.param;
            var language = readCookie(cookieName);

            if (language) {
                var targetUrl = $('a.language_selection_page_link_' + language).get(0).href;
                if (targetUrl) {
                    if (targetUrl.substr(0,4) != 'http') {
                        targetUrl = location.protocol + "//" + location.host + targetUrl;
                    }

                    if (navigator.appName == "Microsoft Internet Explorer" && (parseFloat(navigator.appVersion.split("MSIE")[1])) < 7) {
                        window.location.href = targetUrl;
                    } else {
                        window.location = targetUrl;
                    }
                }
            }
        }
    };

})(jQuery, Drupal);

(function(){
    function readCookie(a, b) {
        b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
        return b ? b.pop() : '';
    }

    window.readCookie = readCookie; // or expose it however you want
})();