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
        $('.owlstack-test-btn').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var platform = $btn.data('platform');
            var $spinner = $btn.closest('td, .owlstack-test-buttons').find('.spinner');
            var $result = $('#owlstack-test-result');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.empty();

            $.ajax({
                url: owlstackAdmin.restUrl + 'test-connection',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': owlstackAdmin.nonce,
                },
                data: JSON.stringify({ platform: platform }),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done(function (response) {
                    var cls = response.success ? 'success' : 'error';
                    $result.html(
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

                    $result.html(
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
     * Test Message handler — sends a sample text message to a platform.
     */
    function initTestMessage() {
        $('.owlstack-test-message-btn').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var platform = $btn.data('platform');
            var type = $btn.data('type');
            var $spinner = $btn.closest('td').find('.spinner');
            var $result = $('#owlstack-test-result');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.empty();

            $.ajax({
                url: owlstackAdmin.restUrl + 'test-message',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': owlstackAdmin.nonce,
                },
                data: JSON.stringify({ platform: platform, type: type }),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done(function (response) {
                    var cls = response.success ? 'success' : 'error';
                    var html = escapeHtml(response.message);

                    if (response.success && response.external_url) {
                        html +=
                            ' <a href="' +
                            escapeHtml(response.external_url) +
                            '" target="_blank" rel="noopener">' +
                            escapeHtml(owlstackAdmin.i18n.viewPost || 'View Post') +
                            '</a>';
                    }

                    $result.html(
                        '<span class="owlstack-test-result ' + cls + '">' + html + '</span>'
                    );
                })
                .fail(function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : owlstackAdmin.i18n.testMessageFailed ||
                              'Failed to send test message.';

                    $result.html(
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
        $('.owlstack-publish-now-btn').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var $spinner = $btn.siblings('.spinner');
            var $resultContainer = $('.owlstack-publish-status');
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
                url: owlstackAdmin.restUrl + 'publish',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': owlstackAdmin.nonce,
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
        initTestMessage();
        initPublishNow();
        initSelectAll();
    });
})(jQuery);
