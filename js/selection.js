$(document).ready(function() {

    var sesskey = $("input[name=sesskey]:hidden").val();

    //MENU SELECTION ACTIONS
    $('select#mentor_menu').change(function() {
        $("#LoadingImage").show();
        var mentor_menu = $('select#mentor_menu').val();

        $("input[name=mentor_menu]:hidden").val(mentor_menu);

        $.ajax({
            type: "POST",
            url: "ajax_mentors_students.php",
            data:{
                'action':mentor_menu,
                'sesskey':sesskey
            },
            dataType: "json",
            success: function (data) {
                var success = data.success;
                var message = data.message;
                var options = data.options;
                //var seat = data.seat;
                if (success === true) {
                    $('select#selectmentor').empty();
                    $('select#selectmentee').empty();
                    $.each(options, function () {
                        $('select#selectmentor').append($("<option></option>").val(this['id']).html(this['label']));
                    });
                    loadStudents(sesskey, '');
                } else {
                    //alert(message);
                }
            }
        });
        $("#LoadingImage").hide();

    });

    $('select#student_menu').change(function() {
        $("#LoadingImage").show();
        var student_menu = $('select#student_menu').val();
        $("input[name=student_menu]:hidden").val(student_menu);
        loadStudents(sesskey, '')
        $("#LoadingImage").hide();
    });

    $('select#selectmentor').change(function() {
        $("#LoadingImage").show();
        var mentorid = $('select#selectmentor').val();
        $.ajax({
            type: "POST",
            url: "ajax_mentors_students.php",
            data:{
                'action':'get_mentees',
                'mentorid':mentorid,
                'sesskey':sesskey
            },
            dataType: "json",
            success: function (data) {
                var success = data.success;
                var message = data.message;
                var options = data.options;
                //var seat = data.seat;
                if (success === true) {
                    $('select#selectmentee').empty();
                    $.each(options, function () {
                        $('select#selectmentee').append($("<option></option>").val(this['id']).html(this['label']));
                    });
                } else {
                    $('select#selectmentee').empty();
                }
            }
        });
        loadStudents(sesskey, '');
        $("#LoadingImage").hide();
    });


    var clear_selections, mentees, move, students;
    students = "select#selectstudent";
    mentees = "select#selectmentee";

    move = function(from, to, filter, action) {
        return function() {
            var items = $(from).children(filter);
            var mentorid = $('select#selectmentor').val();
            var studentids = "0";
            if (mentorid > 0) {
                //alert ("Mentor id : "+mentorid);
                //alert ("Action : "+action);
                items.each(function() {
                    studentids = studentids + ',' + $(this).val();
                });
                //alert ("studentids : "+studentids);
                assignMentor (sesskey, mentorid, studentids, action)
            }
            items.appendTo(to);
            return $(from).children(filter).remove();
        };
    };

    $("#add_button").click(move(students, mentees, ':selected', 'add'));
    $("#add_all").click(move(students, mentees, '*', 'add'));
    $("#remove_button").click(move(mentees, students, ':selected', 'remove'));
    $("#remove_all").click(move(mentees, students, '*', 'remove'));

    //SEARCH INPUT
    $("#student_search").keyup(function() {
        if($(this).val().length > 2) {
            loadStudents (sesskey, $(this).val());
        }
    });

    $('#assign_role').click(function() {
        var link = $(this).attr('url');
        window.location = link;
        return false;
    });

    $('input[type=checkbox]').each(toggleSub);

    //Nested course category selections
    $('input[type=checkbox]').click(function () {
        $(this).parent().find('li input[type=checkbox]').prop('checked', $(this).is(':checked'));
        $(this).parent().find('li input[type=checkbox]').attr('disabled', $(this).is(':checked'));
        var sibs = false;
        $(this).closest('ul').children('li').each(function () {
            if($('input[type=checkbox]', this).is(':checked')) sibs=true;
        })
        $(this).parents('ul').prev().prop('checked', sibs);
    });

    $('#page-blocks-fn_mentor-notification_send .fn-send-confirm input[value=Continue]').click(function () {
        //$('#page-blocks-fn_mentor-notification_send .fn-send-confirm #notice p').html('Messages are being processed.<br>Please wait for confirmation.<br><img style="margin-left: 60px;" src="'+M.cfg.wwwroot+'/blocks/fn_mentor/pix/email3.gif">');
        $('#page-blocks-fn_mentor-notification_send .fn-send-confirm div#notice').hide();
        $('#page-blocks-fn_mentor-notification_send .fn-send-confirm div.notice2').show();
        $(this).closest("form").submit();
    });

})

function toggleSub () {
    if ($(this).is(':checked')) {
        $(this).parent().find('li input[type=checkbox]').prop('checked', $(this).is(':checked'));
        $(this).parent().find('li input[type=checkbox]').attr('disabled', $(this).is(':checked'));
        var sibs = false;
        $(this).closest('ul').children('li').each(function () {
            if ($('input[type=checkbox]', this).is(':checked')) sibs = true;
        })
        $(this).parents('ul').prev().prop('checked', sibs);
    }
}

function loadStudents (sesskey, filter) {
    //alert("loading students");
    $("#LoadingImage").show();
    var student_menu = $('select#student_menu').val();
    $.ajax({
        type: "POST",
        url: "ajax_mentors_students.php",
        data:{
            'action':student_menu,
            'sesskey':sesskey,
            'filter':filter
        },
        dataType: "json",
        success: function (data) {
            var success = data.success;
            var message = data.message;
            var options = data.options;
            if (success === true) {
                $('select#selectstudent').empty();
                $.each(options, function () {
                    $('select#selectstudent').append($("<option></option>").val(this['id']).html(this['label']));
                });
                $('select#selectmentee option').each(function() {
                    //alert($(this).val());
                    $('select#selectstudent option[value="'+$(this).val()+'"]').remove();
                });

            }
        }
    });
    $("#LoadingImage").hide();
}

function assignMentor (sesskey, mentorid, studentids, action) {
    //alert("loading students");
    $("#LoadingImage").show();
    var student_menu = $('select#student_menu').val();
    $.ajax({
        type: "POST",
        url: "ajax_mentor_assign.php",
        data:{
            'action':action,
            'mentorid':mentorid,
            'studentids':studentids,
            'sesskey':sesskey
        },
        dataType: "json",
        success: function (data) {
            var success = data.success;
            var message = data.message;
            if (success === true) {
                //
            } else {
                alert ('Error!');
            }
        }
    });
    loadStudents (sesskey)
    $("#LoadingImage").hide();
}

