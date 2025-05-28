/**
 * Smart TOC Frontend JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Toggle TOC visibility
        $('.smart-toc-toggle-link').on('click', function(e) {
            e.preventDefault();

            var $container = $(this).closest('.smart-toc-container');
            var $listContainer = $container.find('.smart-toc-list-container');
            var $toggleText = $(this);

            if ($listContainer.hasClass('smart-toc-hidden')) {
                // Show TOC
                $listContainer.slideDown(300).removeClass('smart-toc-hidden');
                $toggleText.text('hide');
            } else {
                // Hide TOC
                $listContainer.slideUp(300, function() {
                    $(this).addClass('smart-toc-hidden');
                });
                $toggleText.text('show');
            }
        });

        // Smooth scrolling to headings if enabled
        if (typeof smartTocSettings !== 'undefined' && smartTocSettings.smoothScroll) {
            $('.smart-toc-item a').on('click', function(e) {
                e.preventDefault();

                var target = $(this).attr('href');
                var offset = smartTocSettings.scrollOffset || 30;

                if ($(target).length) {
                    $('html, body').animate({
                        scrollTop: $(target).offset().top - offset
                    }, 500);

                    // Update URL hash without scrolling
                    if (history.pushState) {
                        history.pushState(null, null, target);
                    } else {
                        location.hash = target;
                    }

                    // Set focus on the heading for accessibility
                    $(target).attr('tabindex', -1).focus();
                }
            });
        }

        // Handle back/forward browser buttons with smooth scroll
        if (location.hash && $(location.hash).length) {
            var offset = smartTocSettings.scrollOffset || 30;
            setTimeout(function() {
                window.scrollTo(0, $(location.hash).offset().top - offset);
            }, 100);
        }

        // Highlight current section while scrolling
        if ($('.smart-toc-container').length) {
            var headings = [];

            // Collect all headings with IDs that are in the TOC
            $('.smart-toc-item a').each(function() {
                var id = $(this).attr('href');
                if ($(id).length) {
                    headings.push({
                        id: id,
                        element: $(id),
                        tocLink: $(this)
                    });
                }
            });

            // Set up scroll event handler
            if (headings.length) {
                $(window).on('scroll', debounce(function() {
                    var scrollPosition = $(window).scrollTop();
                    var offset = smartTocSettings.scrollOffset || 30;
                    var currentHeading = null;

                    // Find the current heading
                    for (var i = 0; i < headings.length; i++) {
                        var headingPosition = headings[i].element.offset().top - offset - 5;

                        if (scrollPosition >= headingPosition) {
                            currentHeading = headings[i];
                        } else {
                            break;
                        }
                    }

                    // Highlight the current heading in the TOC
                    if (currentHeading) {
                        $('.smart-toc-item a').removeClass('smart-toc-active');
                        currentHeading.tocLink.addClass('smart-toc-active');
                    }
                }, 100));
            }
        }

        // Check if we need to initially hide the TOC based on user's last preference
        var tocHiddenState = localStorage.getItem('smartTocHidden');
        if (tocHiddenState === 'true') {
            $('.smart-toc-list-container').addClass('smart-toc-hidden').hide();
            $('.smart-toc-toggle-link').text('show');
        }

        // Save TOC state when toggled
        $('.smart-toc-toggle-link').on('click', function() {
            var isHidden = $(this).closest('.smart-toc-container')
                .find('.smart-toc-list-container')
                .hasClass('smart-toc-hidden');

            localStorage.setItem('smartTocHidden', isHidden);
        });
    });

    // Debounce function to limit scroll event handler calls
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

})(jQuery);
