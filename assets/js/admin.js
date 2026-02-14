/**
 * Owlstack WordPress Admin Scripts
 *
 * @package Owlstack\WordPress
 */

(function ($) {
    'use strict';

    /**
     * Test Connection handler for the Settings page.
     */
    function initTestConnection() {
        $('.owlstack-test-connection-btn').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var platform = $btn.data('platform');
            var $spinner = $btn.siblings('.spinner');
            var $result = $btn.siblings('.owlstack-test-result');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.remove();

            $.ajax({
                url: owlstackAdmin.restUrl + 'owlstack/v1/test-connection',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': owlstackAdmin.restNonce,
                },
                data: JSON.stringify({ platform: platform }),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done(function (response) {
                    var cls = response.success ? 'success' : 'error';
                    $btn.after(
                        '<span class="owlstack-test-result ' +
                            cls +
                            '">' +
                            escapeHtml(response.message) +
                            '</span>'
                    );
                })
                .fail(function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : owlstackAdmin.i18n.connectionFailed;

                    $btn.after(
                        '<span class="owlstack-test-result error">' +
                            escapeHtml(msg) +
                            '</span>'
                    );
                })
                .always(function () {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
        });
    }

    /**
     * Publish Now handler for the Meta Box.
     */
    function initPublishNow() {
        $('#owlstack-publish-now').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var $spinner = $btn.siblings('.spinner');
            var $resultContainer = $('#owlstack-publish-result');
            var postId = $btn.data('post-id');

            // Gather selected platforms.
            var platforms = [];
            $('input[name="owlstack_platforms[]"]:checked').each(function () {
                platforms.push($(this).val());
            });

            if (platforms.length === 0) {
                $resultContainer
                    .html(owlstackAdmin.i18n.noPlatformsSelected)
                    .attr('class', 'owlstack-publish-result error')
                    .show();
                return;
            }

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $resultContainer.hide();

            $.ajax({
                url: owlstackAdmin.restUrl + 'owlstack/v1/publish',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': owlstackAdmin.restNonce,
                },
                data: JSON.stringify({
                    post_id: postId,
                    platforms: platforms,
                }),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done(function (response) {
                    var messages = [];
                    var hasFailure = false;

                    $.each(response.results, function (platform, result) {
                        if (result.success) {
                            var link = result.external_url
                                ? ' (<a href="' +
                                  escapeHtml(result.external_url) +
                                  '" target="_blank">' +
                                  owlstackAdmin.i18n.viewPost +
                                  '</a>)'
                                : '';
                            messages.push(
                                '<strong>' +
                                    escapeHtml(platform) +
                                    '</strong>: ✓' +
                                    link
                            );
                        } else {
                            hasFailure = true;
                            messages.push(
                                '<strong>' +
                                    escapeHtml(platform) +
                                    '</strong>: ✗ ' +
                                    escapeHtml(result.error || owlstackAdmin.i18n.unknownError)
                            );
                        }
                    });

                    var cls = response.success
                        ? 'success'
                        : hasFailure
                        ? 'partial'
                        : 'error';

                    $resultContainer
                        .html(messages.join('<br>'))
                        .attr('class', 'owlstack-publish-result ' + cls)
                        .show();
                })
                .fail(function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : owlstackAdmin.i18n.publishFailed;

                    $resultContainer
                        .html(escapeHtml(msg))
                        .attr('class', 'owlstack-publish-result error')
                        .show();
                })
                .always(function () {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
        });
    }

    /**
     * Select-all checkbox for delivery logs.
     */
    function initSelectAll() {
        $('#cb-select-all-1, #cb-select-all-2').on('change', function () {
            var checked = $(this).prop('checked');
            $('input[name="log_ids[]"]').prop('checked', checked);
        });
    }

    /**
     * Escape HTML to prevent XSS.
     */
    function escapeHtml(str) {
        if (!str) return '';

        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));

        return div.innerHTML;
    }

    /**
     * Initialize on DOM ready.
     */
    $(function () {
        if (typeof owlstackAdmin === 'undefined') {
            return;
        }

        initTestConnection();
        initPublishNow();
        initSelectAll();
    });
})(jQuery);
