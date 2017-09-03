$(document).ready(function() {

    $('input[type=checkbox]').each(toggleSub);

    // Nested course category selections.
    $("input[type=checkbox]").click(function () {
        $(this).parent().find("li input[type=checkbox]").prop("checked", $(this).is(":checked"));
        $(this).parent().find("li input[type=checkbox]").attr("disabled", $(this).is(":checked"));
        var sibs = false;
        $(this).closest("ul").children("li").each(function () {
            if($("input[type=checkbox]", this).is(":checked")) { sibs = true; }
        });
        $(this).parents("ul").prev().prop("checked", sibs);
    });

    $('#page-blocks-fn_mentor-notification_send .fn-send-confirm input[value=Continue]').click(function () {
        $('#page-blocks-fn_mentor-notification_send .fn-send-confirm div#notice').hide();
        $('#page-blocks-fn_mentor-notification_send .fn-send-confirm div.notice2').show();
        $(this).closest("form").submit();
    });

});

function toggleSub () {
    if ($(this).is(":checked")) {
        $(this).parent().find("li input[type=checkbox]").prop("checked", $(this).is(":checked"));
        $(this).parent().find("li input[type=checkbox]").attr("disabled", $(this).is(":checked"));
        var sibs = false;
        $(this).closest("ul").children("li").each(function () {
            if ($("input[type=checkbox]", this).is(":checked")) { sibs = true; }
        });
        $(this).parents("ul").prev().prop("checked", sibs);
    }
}
