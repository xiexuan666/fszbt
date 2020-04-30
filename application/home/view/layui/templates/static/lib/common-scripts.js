/*---LEFT BAR ACCORDION----*/
$(function() {
  $('#nav-accordion').dcAccordion({
    eventType: 'click',
    autoClose: true,
    saveState: true,
    disableLink: true,
    speed: 'slow',
    showCount: false,
    autoExpand: true,
    //        cookie: 'dcjq-accordion-1',
    classExpand: 'dcjq-current-parent'
  });
});

var Script = function() {


  //    sidebar dropdown menu auto scrolling

  jQuery('#sidebar .sub-menu > a').click(function() {
    var o = ($(this).offset());
    diff = 250 - o.top;
    if (diff > 0)
      $("#sidebar").scrollTo("-=" + Math.abs(diff), 500);
    else
      $("#sidebar").scrollTo("+=" + Math.abs(diff), 500);
  });



  //    sidebar toggle

  $(function() {
    function responsiveView() {
      var wSize = $(window).width();
      if (wSize <= 768) {
        $('#container').addClass('sidebar-close');
        $('#sidebar > ul').hide();
      }

      if (wSize > 768) {
        $('#container').removeClass('sidebar-close');
        $('#sidebar > ul').show();
      }
    }
    $(window).on('load', responsiveView);
    $(window).on('resize', responsiveView);
  });

  $('.fa-bars').click(function() {
    if ($('#sidebar > ul').is(":visible") === true) {
      $('#main-content').css({
        'margin-left': '0px'
      });
      $('#sidebar').css({
        'margin-left': '-210px'
      });
      $('#sidebar > ul').hide();
      $("#container").addClass("sidebar-closed");
    } else {
      $('#main-content').css({
        'margin-left': '210px'
      });
      $('#sidebar > ul').show();
      $('#sidebar').css({
        'margin-left': '0'
      });
      $("#container").removeClass("sidebar-closed");
    }
  });

  // custom scrollbar
  $("#sidebar").niceScroll({
    styler: "fb",
    cursorcolor: "#4ECDC4",
    cursorwidth: '3',
    cursorborderradius: '10px',
    background: '#404040',
    spacebarenabled: false,
    cursorborder: ''
  });

  //  $("html").niceScroll({styler:"fb",cursorcolor:"#4ECDC4", cursorwidth: '6', cursorborderradius: '10px', background: '#404040', spacebarenabled:false,  cursorborder: '', zindex: '1000'});

  // widget tools

  jQuery('.panel .tools .fa-chevron-down').click(function() {
    var el = jQuery(this).parents(".panel").children(".panel-body");
    if (jQuery(this).hasClass("fa-chevron-down")) {
      jQuery(this).removeClass("fa-chevron-down").addClass("fa-chevron-up");
      el.slideUp(200);
    } else {
      jQuery(this).removeClass("fa-chevron-up").addClass("fa-chevron-down");
      el.slideDown(200);
    }
  });

  jQuery('.panel .tools .fa-times').click(function() {
    jQuery(this).parents(".panel").parent().remove();
  });


  //    tool tips

  $('.tooltips').tooltip();

  //    popovers

  $('.popovers').popover();



  // custom bar chart

  if ($(".custom-bar-chart")) {
    $(".bar").each(function() {
      var i = $(this).find(".value").html();
      $(this).find(".value").html("");
      $(this).find(".value").animate({
        height: i
      }, 2000)
    })
  }

}();

jQuery(document).ready(function( $ ) {

  // Go to top
  $('.go-top').on('click', function(e) {
    e.preventDefault();
    $('html, body').animate({scrollTop : 0},500);
  });
  
  
  //该按钮有个class名为.tab-on，为它绑定一个事件
var v = false;//定义一个布尔型变量，来判断显示关闭或者打开
$( ".tab-on" ).click( function() {//给按钮绑定点击事件
    if( v ) {    //如果为真的时候，我这里就打开
        $( this ).html( "关闭" );
		$('.grey-all').css('background','#fff');
		$('.closed').css('display','none');
		$('.m-edit .a1').css('color','#48cfad');
		
        v = false; //由于文字已更改，所以我们要改变变量的值
    } else {
        $( this ).html( "启用" );
		$('.grey-all').css('background','#ddd');
		$('.closed').css('display','inline-block');
		$('.m-edit .a1').css('color','#999');
        v = true;
    }
} );
 
 


	
	//年龄范围只能输入数字
		$(document).on('keyup', '#N1', function () {
			if (!$("#N1").val().match(/^\d*$/)) {
				$('.abcde').fadeIn();
				$('.abcde').text("*只能输入纯数字");
				$('#N1').addClass('error_input');
				
				
				return;
			} else {
				$('.abcde').fadeOut();
				$('.abcde').text("");
				$('#N1').removeClass('error_input');
			}
			
			
			
		})
		

 $(document).on('keyup', '#N2', function () {
		if(Number($('#N1').val())>Number($('#N2').val())||(!$("#N2").val().match(/^\d*$/))){
		       $('.abcde').fadeIn();
			   $('.abcde').text("只能输入纯数字且输入的最高年龄应比最低年龄大！请重新输入！");
			   $('.#N2').addClass('error_input');
			   return;
		}else {
				$('.abcde').fadeOut();
				$('.abcde').text("");
				$('#N2').removeClass('error_input');
			}
			
               				
   });
   
   //岗位要求不能为空
    $(document).on('keyup', '.m-textarea', function () {
		if ($(".m-textarea").val() == ""){ 
		    $(".m-textarea").focus(); 
			 $('.abcde').fadeIn();
			$('.abcde').text("输入内容不能为空！");
		    
		}  else{
           $('.abcde').fadeOut();
		    $('.abcde').text("");
		     
		}
               				
   });
   
   
  
});

 
