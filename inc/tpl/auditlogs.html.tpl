
<script type="text/javascript">
/* global jQuery */
/* jshint camelcase:false */
jQuery(function ($) {
    var writeQueueSize = function (queueSize)
    {
        if (queueSize === 0) {
            $('.sucuriscan-auditlogs-sendlogs-panel').addClass('sucuriscan-hidden');
        } else {
            var msg = '\x20@@SUCURI.AuditLogsQueue@@\x20&mdash;\x20';
            $('.sucuriscan-auditlogs-sendlogs-panel').removeClass('sucuriscan-hidden');
            $('.sucuriscan-auditlogs-sendlogs-response').html((queueSize).toString() + msg);
        }
    };

    var sucuriscanLoadAuditLogs = function (page, reset) {
        var url = '%%SUCURI.AjaxURL.Dashboard%%';

        if (page !== undefined && page > 0) {
            url += '&paged=' + page;
        }

        if (reset === true) {
            $('.sucuriscan-auditlog-response').html('<em>@@SUCURI.Loading@@</em>');
        }

        $('.sucuriscan-pagination-loading').html('@@SUCURI.Loading@@');

        $.post(url, {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'get_audit_logs',
        }, function (data) {
            $('.sucuriscan-pagination-loading').html('');

            writeQueueSize(data.queueSize);

            if (data.content !== undefined) {
                $('.sucuriscan-auditlog-response').html(data.content);

                if (data.pagination !== '') {
                    $('.sucuriscan-auditlog-table .sucuriscan-pagination').html(data.pagination);
                }
            } else if (typeof data === 'object') {
                $('.sucuriscan-auditlog-response').html(
                '<textarea class="sucuriscan-full-textarea">' +
                JSON.stringify(data) + '</textarea>');
                $('.sucuriscan-auditlog-table .sucuriscan-pagination').html('');
            } else {
                $('.sucuriscan-auditlog-response').html(data);
                $('.sucuriscan-auditlog-table .sucuriscan-pagination').html('');
            }
        });
    }

    setTimeout(function () {
        sucuriscanLoadAuditLogs(0, true);
    }, 100);

    $('.sucuriscan-auditlog-table').on('click', '.sucuriscan-pagination-link', function (event) {
        event.preventDefault();
        sucuriscanLoadAuditLogs($(this).attr('data-page'));
    });

    $('.sucuriscan-auditlog-table').on('click', '.sucuriscan-auditlogs-reset', function (event) {
        event.preventDefault();
        $.post('%%SUCURI.AjaxURL.Dashboard%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'reset_auditlogs_cache',
        }, function () {
            sucuriscanLoadAuditLogs(0, true);
        });
    });

    $('.sucuriscan-auditlog-table').on('click', '.sucuriscan-auditlogs-sendlogs', function (event) {
        event.preventDefault();
        $('.sucuriscan-auditlogs-sendlogs-response').html('@@SUCURI.Loading@@');
        $.post('%%SUCURI.AjaxURL.Dashboard%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'auditlogs_send_logs',
        }, function (data) {
            writeQueueSize(data.queueSize);
        });
    });
});
</script>

<div class="sucuriscan-auditlog-table">
    <div class="sucuriscan-auditlog-response">
        <em>@@SUCURI.Loading@@</em>
    </div>

    <div class="sucuriscan-clearfix">
        <ul class="sucuriscan-pull-left sucuriscan-pagination">
            <!-- Populated via JavaScript -->
        </ul>

        <div class="sucuriscan-pull-right sucuriscan-pagination-loading">
            <!-- Populated via JavaScript -->
        </div>
    </div>

    <div class="sucuriscan-auditlog-footer">
        <div class="sucuriscan-pull-left">
            <small>@@SUCURI.AuditLogsCache@@ &mdash; <a href="#" class="sucuriscan-auditlogs-reset">@@SUCURI.Refresh@@</a></small>
        </div>

        <div class="sucuriscan-pull-right sucuriscan-hidden sucuriscan-auditlogs-sendlogs-panel">
            <small class="sucuriscan-auditlogs-sendlogs-response"></small>
            <small><a href="#" class="sucuriscan-auditlogs-sendlogs">@@SUCURI.SendLogs@@</a></small>
        </div>
    </div>
</div>
