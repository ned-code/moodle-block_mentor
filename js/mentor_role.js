$(document).ready(function() {
    var pageid = $('body').attr('id');
    var moveuser, assign, loadpotentials;
    var sesskey = M.cfg.sesskey;

    moveuser = function(from, to, target, filter, action) {
        var targetid;

        if (target !== false) {
            targetid = $(target).val();
        } else {
            targetid = 0;
        }

        $("input.assign-group-button[type=button]").attr("disabled", true);
        $(to).css('background', 'url(' + M.util.image_url('i/loading', 'moodle') + ') no-repeat center center');
        $(from).children(filter).each(function () {
            assign($(this).val(), targetid, action);
            $(this).appendTo(to);
        });
        $(to).find('option').removeAttr("selected");
        $(from).children(filter).remove();
        $(to).css('background', '');
        $("input.assign-group-button[type=button]").attr("disabled", false);

        return true;
    };

    assign = function(userid, targetid, action) {
        $.ajax({
            type  : "POST",
            async : false,
            url   : M.cfg.wwwroot + '/blocks/fn_mentor/ajax_group_action.php',
            data:{
                'userid'    : userid,
                'targetid'  : targetid,
                'action'    : action,
                'sesskey'   : sesskey
            },
            dataType: "json",
            cache : false
        });
        return true;
    };

    loadpotentials = function (filter) {
        $('#potential-mentor').css('background', 'url(' + M.util.image_url('i/loading', 'moodle') + ') no-repeat center center');
        $.ajax({
            type: "POST",
            async : false,
            url   : M.cfg.wwwroot + '/blocks/fn_mentor/ajax_mentors_students.php',
            data:{
                'action'  : 'all_users',
                'sesskey' : sesskey,
                'filter'  : filter
            },
            dataType: "json",
            success: function (data) {
                var success = data.success;
                var message = data.message;
                var options = data.options;
                if (success === true) {
                    $('#potential-mentor').empty();
                    $.each(options, function () {
                        $('#potential-mentor').append($("<option></option>").val(this['id']).html(this['label']));
                    });
                }
            }
        });
        $('#potential-mentor').css('background', '');
        return true;
    };

    $("#potential-mentor-search-text").keyup(function (){
        var search = $(this).val();
        loadpotentials(search);
    });

    $("#potential-mentor-clear-btn").click(function (){
        $("#potential-mentor-search-text").val('');
        loadpotentials('');
    });

    $("#add-mentor-btn").click(function () {
        moveuser('#potential-mentor', '#selected-mentor', false, ':selected', 'assign-mentor');
    });

    $("#remove-mentor-btn").click(function () {
        moveuser('#selected-mentor', '#potential-mentor', false, ':selected', 'unassign-mentor')
    });

});

