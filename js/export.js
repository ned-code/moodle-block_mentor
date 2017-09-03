$(document).ready(function() {
    $("#id_includeenrolledusers").click(function () {
        if ($(this).is(':checked')) {
            $("#id_includeallusers").prop('checked', false)
        }
    });
    $("#id_includeallusers").click(function () {
        if ($(this).is(':checked')) {
            $("#id_includeenrolledusers").prop('checked', false)
        }
    });
});
