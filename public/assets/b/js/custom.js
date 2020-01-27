// JavaScript Document

$(document).ready(function(){	
  
// responsive navigation js for 768px
var wid = $(window).width();
$(".table1 tr:nth-child(odd)").addClass("odd");
    $("ul.alter li:nth-child(odd)").addClass("odd");
    $("ul.alter li:last-child").addClass("last");
$(".setting-option").mouseover(function(){
		$('.setting-option ul').show();	
	});
	
	$(".setting-option").mouseout(function(){
		$('.setting-option ul').hide();	
	});
$(".nav li").click(function(){
	$(this).find("ul").slideToggle();	
	$(this).toggleClass('active');	
});

$(".nav li ul li a").click(function(e){
	$(".nav li ul li a").removeClass('this');
	$(this).toggleClass('this');	
	e.stopPropagation();
});

// user area box
$(".user-area .userName").click(function(){
	$('.drop').toggle();	
	
});

$(".info a").click(function(e){
	e.stopPropagation();
	
});

// datepicker
   $(function() {
        $( "#datepicker, #datepicker2" ).datepicker({buttonImage:
            'b/images/calendar.gif'});
    });

// service prodiver
$(".serviceProvider .col1 li").click(function(){
	$(this).toggleClass('select');	
	
});

	
	
// ----------------tabs	--------------

$('.tabContent').hide();
$('.tabContent:first').show();
$('.tabLink li:first').addClass('active');

$(".tabLink li a").click(function(){
	$(".tabLink li").removeClass('active');	
	$(this).parent().addClass('active');		
	var id = $(this).attr('href');
	$('.tabContent').hide();	
	$(id).show();	
	return false;	
});


//$("a.notes-btn").click(function () {
//        $(".notes-popup").show();
//    });     
//     
//    //close custom popup  
//    $(".notes-popup a.close, .notes-popup .submit-btn").click(function () {
//        $(".notes-popup").hide();
//    }); 



});





