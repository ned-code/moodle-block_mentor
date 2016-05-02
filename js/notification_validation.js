// Aa custom onComplete handler to prevent form submits for the demo.
function myOnComplete() {
    return true;
}

var rules = [];

// Standard form fields.
rules.push("required,name, Rule name is required.");
rules.push("digits_only, g4_value, This field may only contain digits.");
rules.push("digits_only, g6_value, This field may only contain digits.");
rules.push("digits_only, n1_value, This field may only contain digits.");
rules.push("digits_only, n2_value, This field may only contain digits.");
rules.push("required, period, This field may only contain digits.");
rules.push("digits_only, period, This field may only contain digits.");

// A custom validation function.
function my_custom_function() {
    var prime_nums_str = "1|2|3|5|7|11|13|17|19|23|29|31|37|41|43|47|53|59|61|67|71|73|79|83|89|97";
    var prime_numbers = prime_nums_str.split("|");

    var val = document.getElementById("prime_number").value;

    var is_valid_num = false;
    for (i = 0; i < prime_numbers.length; i++)
    {
        if (prime_numbers[i] == val) {
            is_valid_num = true; }
    }

    if (!is_valid_num)
    {
        var field = document.getElementById("prime_number");
        return [[field, "Please enter a prime number under 100."]];
    }

    return true;
}

$(document).ready(function() {
    $("#notification_form").RSV({
        onCompleteHandler: myOnComplete,
        rules: rules
    });
});