$(document).ready(function() {
    var pageid = $('body').attr('id');
    var moveuser, assign, loadpotentials, loadselectedmentees;
    var sesskey = M.cfg.sesskey;
    var groupid = $('#potential-mentor-filter-form input[name=groupid]').val();


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
        if (groupid == 0) {
            $("#remove-mentor-btn").attr("disabled", true);
        }

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
                'groupid'   : groupid,
                'action'    : action,
                'sesskey'   : sesskey
            },
            dataType: "json",
            cache : false
        });
        return true;
    };

    loadpotentials = function (filter, type, mentorid) {
        var filter_menu = $('#potential-' + type + '-filter').val();
        var sessionfilter = false;
        $('#potential-' + type).css('background', 'url(' + M.util.image_url('i/loading', 'moodle') + ') no-repeat center center');

        if ((mentorid > 0) && (type == 'mentee')) {
            filter_menu = 'get_mentees'
            sessionfilter =  true;
        }

        $.ajax({
            type: "POST",
            async : false,
            url   : M.cfg.wwwroot + '/blocks/fn_mentor/ajax_mentors_students.php',
            data:{
                'action'  : filter_menu,
                'mentorid' : mentorid,
                'sessionfilter' : sessionfilter,
                'sesskey' : sesskey,
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

    loadselectedmentees = function (mentorid, groupid) {
        $('#selected-mentee').css('background', 'url(' + M.util.image_url('i/loading', 'moodle') + ') no-repeat center center');
        $.ajax({
            type: "POST",
            async : false,
            url   : M.cfg.wwwroot + '/blocks/fn_mentor/ajax_mentors_students.php',
            data  : {
                'action'  : 'get_group_mentees',
                'mentorid': mentorid,
                'groupid': groupid,
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

    $("#potential-mentor-search-text").keyup(function (){
        var search = $(this).val();
        loadpotentials(search, 'mentor', 0);
    });

    $("#potential-mentor-clear-btn").click(function (){
        $("#potential-mentor-search-text").val('');
        loadpotentials('', 'mentor', 0);
    });

    if (groupid > 0) {
        $("#add-mentor-btn").click(function () {
            moveuser('#potential-mentor', '#selected-mentor', false, ':selected', 'add-mentor');
        });

        $("#remove-mentor-btn").click(function () {
            moveuser('#selected-mentor', '#potential-mentor', false, ':selected', 'remove-mentor')
        });
        $('#selected-mentor').change(function() {
            var mentorid = $(this).val();
            var mentorname = $(this).find('option:selected').text();
            var groupleadertext = $('#btn-group-leader-toggle').attr('value');
            if (mentorname.startsWith("[GT]")) {
                groupleadertext = groupleadertext.replace("Set", "Unset");
                $('#btn-group-leader-toggle').attr('value', groupleadertext)
            } else {
                groupleadertext = groupleadertext.replace("Unset", "Set");
                $('#btn-group-leader-toggle').attr('value', groupleadertext)
            }
            $("#selected-mentee-label").html('Group mentees for ' + mentorname);
            $("#potential-mentee-label").html('Available mentees for ' + mentorname);
            loadpotentials('', 'mentee', mentorid);
            loadselectedmentees(mentorid, groupid);
        });

        $("#btn-group-leader-toggle").click(function () {
            var mentorid = $('#selected-mentor').val();

            var groupleadertext = $(this).attr('value');
            $(this).attr("disabled", true);
            $.ajax({
                type  : "POST",
                async : false,
                url   : M.cfg.wwwroot + '/blocks/fn_mentor/ajax_group_action.php',
                data:{
                    'userid'    : mentorid,
                    'groupid'   : groupid,
                    'action'    : 'toggle-group-leader',
                    'sesskey'   : sesskey
                },
                dataType: "json",
                cache : false,
                success: function (data) {
                    var success = data.success;
                    var message = data.message;
                    var options = data.options;
                    if (success === true) {
                        var mentorname = $('#selected-mentor').find('option:selected').text();
                        if (mentorname.startsWith("[GT]")) {
                            mentorname = mentorname.replace("[GT] ", "");
                        } else {
                            mentorname = "[GT] " + mentorname;
                        }
                        $('#selected-mentor').find('option:selected').text(mentorname);

                        $('#selected-mentor').trigger( "change" );
                    }
                }
            });
            $(this).attr("disabled", false);
        });
        $("#add-mentee-btn").click(function () {
            var mentorid = $('#selected-mentor').val();
            if (mentorid > 0) {
                moveuser('#potential-mentee', '#selected-mentee', '#selected-mentor', ':selected', 'add-mentee-group')
            } else {
                alert(pleaseselectamentor)
            }
        });

        $("#remove-mentee-btn").click(function () {
            moveuser('#selected-mentee', '#potential-mentee', '#selected-mentor', ':selected', 'remove-mentee-group')
        });

    }
    else {
        // No group.
        $("#add-mentor-btn").attr("disabled", true);
        $("#remove-mentor-btn").attr("disabled", true);
    }
});

