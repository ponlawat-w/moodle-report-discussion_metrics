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
                    var starttime = $('#id_starttime').val();
                    var endtime = $('#id_endtime').val();
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