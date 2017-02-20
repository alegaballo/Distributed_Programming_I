/**
 * Created by Alessandro Gaballo on 06/06/2016.
 */
var url = "https://localhost/exam_pdI/delete.php";
$(window).on("resize", myfunction);

if (!navigator.cookieEnabled) {
    location.replace("blocked.html");
}

function myfunction() {
    if ($(window).width() < 500) {
        //alert("ciao");
        //$("#toggleMe").addClass("collapsed");
        $('#collapse1').removeClass("collapse in");
        $('#collapse1').addClass("collapse");
        //$('#toggleMe').append("<span class='glyphicon glyphicon-collapse-down'></span>")

        $('#icon').removeClass('glyphicon glyphicon-collapse-up right');
        $('#icon').addClass('glyphicon glyphicon-collapse-down right');

    }
    else {
        $('#collapse1').removeClass("collapse");
        $('#collapse1').addClass("collapse in");
        $('#icon').removeClass('glyphicon glyphicon-collapse-down right');
        $('#icon').addClass('glyphicon glyphicon-collapse-up right');
        toggleIcon();
    }
}


function toggleIcon() {
    $("#icon").toggleClass("glyphicon glyphicon-collapse-up right").toggleClass("glyphicon glyphicon-collapse-down right");
}