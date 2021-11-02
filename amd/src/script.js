define(['jquery'], function () {
    return {
        init: function () {
            $(document).ready(function () {
                $('.download').click(function () {
                    top.window.onbeforeunload = null;
                    var type = $('#id_type').val();
                    var forum = $('#id_forum').val();
                    var group = $('#id_group').val();
                    var grouping = $('#id_grouping').val();
                    var starttime_day = $('#id_starttime_day').val();
                    var starttime_month = $('#id_starttime_month').val();
                    var starttime_year = $('#id_starttime_year').val();
                    var starttime_hour = $('#id_starttime_hour').val();
                    var starttime_minute = $('#id_starttime_minute').val();
                    var starttime = new Date(starttime_month + "-" + starttime_day + "-" + starttime_year + " " + starttime_hour + ":" + starttime_minute).getTime();
                    var endtime_day = $('#id_endtime_day').val();
                    var endtime_month = $('#id_endtime_month').val();
                    var endtime_year = $('#id_endtime_year').val();
                    var endtime_hour = $('#id_endtime_hour').val();
                    var endtime_minute = $('#id_endtime_minute').val();
                    var endtime = new Date(endtime_month + "-" + endtime_day + "-"
                        + endtime_year + " " + endtime_hour + ":" + endtime_minute).getTime();
                    var onlygroupworks = $('#id_onlygroupworks').is(':checked');
                    if (onlygroupworks == true) {
                        onlygroupworks = 1;
                    } else {
                        onlygroupworks = 0;
                    }
                    var stale_reply_days = $('#id_stale_reply_days').val();
                    var courseid = $('#courseid1').val();
                    window.location.replace('download.php?type=' + type + '&forum=' + forum + '&group=' + group + '&starttime=' + starttime +
                        '&endtime=' + endtime + '&stale_reply_days=' + stale_reply_days + '&course=' + courseid + '&grouping=' + grouping + '&onlygroupworks=' + onlygroupworks);

                });
            })
        }
    }
})