
// JavaScript Document
jQuery(document).ready(function() {

    $(".table1 tr:nth-child(odd)").addClass("odd");
    $("ul.alter li:nth-child(odd)").addClass("odd");
    $("ul.alter li:last-child").addClass("last");


    // user area box
    $(".user-area .btn3").click(function() {
        $('.drop').toggle();
    });

    // setting btn
    $(".setting-option").mouseover(function() {
        $('.setting-option ul').show();
    });

    $(".setting-option").mouseout(function() {
        $('.setting-option ul').hide();
    });

    $(".share-sec").mouseover(function() {
        $(this).find("ul").show();
    });

    $(".share-sec").mouseout(function() {
        $(this).find("ul").hide();
    });



    $(".share-sec2").mouseover(function() {
        $(this).find("ul").show();
    });

    $(".share-sec2").mouseout(function() {
        $(this).find("ul").hide();
    });
   

});

function isSessionExpired()
{
    var targateUrl = BASE_URL + '/isusexpired';
    jQuery.ajax({
        url: targateUrl,
        type: 'get',
        async: false,
        complete: function(response) {
            console.log(response);
            return response;
        }
    });
}
