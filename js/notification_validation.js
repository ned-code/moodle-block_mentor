// a custom onComplete handler to prevent form submits for the demo
function myOnComplete()
{
//alert("The form validates! (normally, it would submit the form here).");
return true;
}

var rules = [];

// standard form fields
rules.push("required,name, Rule name is required.");
rules.push("digits_only, g3_value, This field may only contain digits.");
rules.push("digits_only, g4_value, This field may only contain digits.");
rules.push("digits_only, g5_value, This field may only contain digits.");
rules.push("digits_only, g6_value, This field may only contain digits.");
rules.push("digits_only, n1_value, This field may only contain digits.");
rules.push("digits_only, n2_value, This field may only contain digits.");
rules.push("required, period, This field may only contain digits.");
rules.push("digits_only, period, This field may only contain digits.");

//rules.push("required,email,Please enter your email address.");
//rules.push("valid_email,email,Please enter a valid email address.");
/*
// date fields
rules.push("valid_date,any_date_month,any_date_day,any_date_year,any_date,Please enter a valid date.");
rules.push("valid_date,later_date_month,later_date_day,later_date_year,later_date,Please enter a date later than today.");

// Numbers / alphanumeric fields
rules.push("required,any_integer,Please enter an integer.");
rules.push("digits_only,any_integer,This field may only contain digits.");
rules.push("digits_only,number_range,This field may only contain digits.");
rules.push("range=1-100,number_range,Please enter a number between 1 and 100.");
rules.push("range>100,number_range_greater_than,Please enter a number greater than 100.");
rules.push("range>=100,number_range_greater_than_or_equal,Please enter a number greater than or equal to 100.");
rules.push("range<100,number_range_less_than,Please enter a number less than 100.");
rules.push("range<=100,number_range_less_than_or_equal,Please enter a number less than or equal to 100.");
rules.push("letters_only,letter_field,Please only enter letters (a-Z) in this field.");
rules.push("required,alpha_field,Please enter an alphanumeric (0-9 a-Z) string.");
rules.push("is_alpha,alpha_field,Please only enter alphanumeric characters (0-9 a-Z) in this field.");
rules.push("custom_alpha,custom_alpha_field1,LLL-VVV,Please enter a string of form LLL-VVV - where L is an uppercase letter and V is an uppercase vowel.");
rules.push("custom_alpha,custom_alpha_field2,DDxxx,Please enter a string of form DDxxx.");
rules.push("custom_alpha,custom_alpha_field3,EEXX,Please enter a string of form EEXX.");
rules.push("custom_alpha,custom_alpha_field4,VVvvllFF,Please enter a string of form VVvvllFF.");
rules.push("custom_alpha,custom_alpha_field5,#XccccCCCC,Please enter a string of form #XccccCCCC.");
rules.push("reg_exp,reg_exp_field1,^\s*(red|orange|yellow|green|blue|indigo|violet|pink|white)\s*$,Please enter your favourite colour in lowercase (e.g. \"red\" or \"blue\")");
rules.push("required,reg_exp_field2,Please enter your favourite colour (e.g. \"red\" or \"blue\")");
rules.push("reg_exp,reg_exp_field2,^\s*(red|orange|yellow|green|blue|indigo|violet|pink|white)\s*$,i,Please enter your favourite colour (e.g. \"red\" or \"blue\")");

// Length of field input
rules.push("length=2,char_length,Please enter a value that is exactly two characters long.");
rules.push("length=3-5,char_length_range,Please enter a value that is between 3 and 5 characters in length.");
rules.push("length>5,char_length_greater_than,Please enter a value that is over 5 characters long.");
rules.push("length>=5,char_length_greater_than_or_equal,Please enter a value that is at least 5 characters long.");
rules.push("length<5,char_length_less_than,Please enter a value that is less than 5 characters long.");
rules.push("length<=5,char_length_less_than_or_equal,Please enter a value that is less than or equal to 5 characters.");

// custom functions
rules.push("function,my_custom_function");

// password fields
rules.push("required,password,Please enter a password.");
rules.push("same_as,password,password_2,Please ensure the passwords you enter are the same.");

// conditional (if-else) fields
rules.push("required,gender,Please enter your gender.");
rules.push("if:gender=male,required,male_question,Please enter the name of your favourite Care Bear.");
rules.push("if:gender=female,required,female_question,Please indicate what max weight you can bench.");
*/

// a custom validation function
function my_custom_function()
{
    var prime_nums_str = "1|2|3|5|7|11|13|17|19|23|29|31|37|41|43|47|53|59|61|67|71|73|79|83|89|97";
    var prime_numbers = prime_nums_str.split("|");

    var val = document.getElementById("prime_number").value;

    var is_valid_num = false;
    for (i=0; i<prime_numbers.length; i++)
    {
        if (prime_numbers[i] == val)
            is_valid_num = true;
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