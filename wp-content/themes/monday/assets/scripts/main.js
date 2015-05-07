/* ========================================================================
 * DOM-based Routing
 * Based on http://goo.gl/EUTi53 by Paul Irish
 *
 * Only fires on body classes that match. If a body class contains a dash,
 * replace the dash with an underscore when adding it to the object below.
 *
 * .noConflict()
 * The routing is enclosed within an anonymous function so that you can
 * always reference jQuery with $, even when in .noConflict() mode.
 *
 * Google CDN, Latest jQuery
 * To use the default WordPress version of jQuery, go to lib/config.php and
 * remove or comment out: add_theme_support('jquery-cdn');
 * ======================================================================== */

(function($) {

    // Use this variable to set up the common and page specific functions. If you
    // rename this variable, you will also need to rename the namespace below.
    var Sage = {
        // All pages
        'common': {
            init: function() {
                // JavaScript to be fired on all pages
            },
            finalize: function() {
                $(document).on('click', '#mobile-nav-close', function() {
                    $('.navbar-collapse').collapse('hide');
                });
                // JavaScript to be fired on all pages, after page specific JS is fired
                $('.fancybox.iframe').on('click', function(e) {
                    e.preventDefault();
                    $.fancybox.open({
                        href: window.home_url + '/investors.html',
                        type: 'iframe'
                    });
                });
            }
        },
        // Home page
        'home': {
            init: function() {
                // JavaScript to be fired on the home page
            },
            finalize: function() {
                // JavaScript to be fired on the home page, after the init JS
                $('#slides').superslides({
                    animation: 'fade'
                });
            }
        },
        'case_study': {
            init: function() {

                function equalCols() {
                    if ($(window).width() > 479) {
                        $('.case-image').height($('.case-description-col').height());
                        $('.image-pagination').css('top', 0);
                    } else {
                        $('.case-image').height('auto');
                        $('.image-pagination').css('top', $('.case-image img').height());
                    }
                }

                equalCols();

                $(window).resize(function() {
                    equalCols();
                    $('.ngg-casestudy').height($('.case-study.active').height());
                });

                var caseStudy = $('.ngg-casestudy .case-study').first();
                var csHeight = $(caseStudy).height();
                $('.ngg-casestudy').height(csHeight);
                $(caseStudy).css('visibility', 'visible').addClass('active');

                $('.image-pagination a').on('click', function(e) {
                    e.preventDefault();
                    $('.image-pagination a').removeClass('active');
                    $(this).addClass('active');

                    var idx = parseInt($(this).text()) - 1;
                    caseStudy = $('.ngg-casestudy .case-study')
                        .removeClass('active')
                        .css('visibility', 'hidden')
                        .eq(idx);
                    var csHeight = $(caseStudy).height();

                    $('.ngg-casestudy').height(csHeight);
                    $(caseStudy).css('visibility', 'visible')
                        .addClass('active')
                        .find('img')
                        .imageScale('scale');
                });

                $("img.scale").imageScale({
                    fadeInDuration: 1000,
                    rescaleOnResize: true
                });
            }
        }
    };

    // The routing fires all common scripts, followed by the page specific scripts.
    // Add additional events for more control over timing e.g. a finalize event
    var UTIL = {
        fire: function(func, funcname, args) {
            var fire;
            var namespace = Sage;
            funcname = (funcname === undefined) ? 'init' : funcname;
            fire = func !== '';
            fire = fire && namespace[func];
            fire = fire && typeof namespace[func][funcname] === 'function';

            if (fire) {
                namespace[func][funcname](args);
            }
        },
        loadEvents: function() {
            // Fire common init JS
            UTIL.fire('common');

            // Fire page-specific init JS, and then finalize JS
            $.each(document.body.className.replace(/-/g, '_').split(/\s+/), function(i, classnm) {
                UTIL.fire(classnm);
                UTIL.fire(classnm, 'finalize');
            });

            // Fire common finalize JS
            UTIL.fire('common', 'finalize');
        }
    };

    // Load Events
    $(document).ready(UTIL.loadEvents);

})(jQuery); // Fully reference jQuery after this point.
