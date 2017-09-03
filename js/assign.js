$(document).ready(function() {
    var pageid = $('body').attr('id');
    var moveuser, assign, loadpotentials, loadselectedmentees;
    var sesskey = M.cfg.sesskey;
    var pleaseselectamentor = M.str.block_fn_mentor.pleaseselectamentor;

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
        $("#remove-mentor-btn").attr("disabled", true);

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
                'groupid'   : 0,
                'action'    : action,
                'sesskey'   : sesskey
            },
            dataType: "json",
            cache : false
        });
        return true;
    };

    loadpotentials = function (filter, type) {
        var filter_menu = $('#potential-' + type + '-filter').val();
        $('#potential-' + type).css('background', 'url(' + M.util.image_url('i/loading', 'moodle') + ') no-repeat center center');
        $.ajax({
            type: "POST",
            async : false,
            url   : M.cfg.wwwroot + '/blocks/fn_mentor/ajax_mentors_students.php',
            data:{
                'action'  : filter_menu,
                'sesskey' : sesskey,
                'sessionfilter' : true,
                'filter'  : filter
            },
            dataType: "json",
            success: function (data) {
                var success = data.success;
                var message = data.message;
                var options = data.options;
                if (success === true) {
                    $('#potential-' + type).empty();
                    $.each(options, function () {
                        $('#potential-' + type).append($("<option></option>").val(this['id']).html(this['label']));
                    });
                }
            }
        });
        $('#potential-' + type).css('background', '');
        return true;
    };

    loadselectedmentees = function (mentorid) {
        $('#selected-mentee').css('background', 'url(' + M.util.image_url('i/loading', 'moodle') + ') no-repeat center center');
        $.ajax({
            type: "POST",
            async : false,
            url   : M.cfg.wwwroot + '/blocks/fn_mentor/ajax_mentors_students.php',
            data  : {
                'action'  : 'get_mentees',
                'mentorid': mentorid,
                'sesskey' : sesskey
            },
            dataType: "json",
            success: function (data) {
                var success = data.success;
                var message = data.message;
                var options = data.options;

                if (success === true) {
                    $('#selected-mentee').empty();
                    $.each(options, function () {
                        $('#selected-mentee').append($("<option></option>").val(this['id']).html(this['label']));
                        $('#potential-mentee' + " option[value='" + this['id'] + "']").remove();
                    });
                } else {
                    $('#selected-mentee').empty();
                }
            }
        });
        $('#selected-mentee').css('background', '');
        return true;
    };

    $("#add-mentee-btn").click(function () {
        var mentorid = $('#selected-mentor').val();
        if (mentorid > 0) {
            moveuser('#potential-mentee', '#selected-mentee', '#selected-mentor', ':selected', 'add-mentee')
        } else {
            alert(pleaseselectamentor)
        }
    });

    $("#remove-mentee-btn").click(function () {
        moveuser('#selected-mentee', '#potential-mentee', '#selected-mentor', ':selected', 'remove-mentee')
    });

    $('#selected-mentor').change(function() {
        var mentorid = $(this).val();

        loadpotentials('', 'mentee');
        loadselectedmentees(mentorid);
    });

    $("#add-mentor-btn").click(function () {
        moveuser('#potential-mentor', '#selected-mentor', false, ':selected', 'add-mentor');
        loadselectedmentees($('#selected-mentor').val());
    });

    // No group.
    $("#remove-mentor-btn").attr("disabled", true);
    $('#potential-mentor').removeAttr('multiple');

    $("#potential-mentor-search-text").keyup(function (){
        var search = $(this).val();
        loadpotentials(search, 'mentor');
    });

    $("#potential-mentee-search-text").keyup(function() {
        var search = $(this).val();
        loadpotentials(search, 'mentee');
    });

    $("#potential-mentor-clear-btn").click(function (){
        $("#potential-mentor-search-text").val('');
        loadpotentials('', 'mentor');
    });

    $("#potential-mentee-clear-btn").click(function (){
        $("#potential-mentee-search-text").val('');
        loadpotentials(sesskey, '', 'mentee');
    });
});

