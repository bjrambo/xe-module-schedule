// 종일 일정의 배경색에 따라 글자색 바꾸기
$('.schedule_daily_body .schedule_list.is_allday a').each(function() {
	var bg_color = $(this).parent().css('background-color'),
		bg_colors = bg_color.split(',');
	var R = parseInt(bg_colors[0].split('(')[1])/255,
		G = parseInt(bg_colors[1])/255,
		B = parseInt(bg_colors[2].split(')')[0])/255;
	var cMax = Math.max(R, G, B),
		cMin = Math.min(R, G, B);
	var lightness = (cMax + cMin) / 2;

	if ( lightness >= 0.75 ) {
		$(this).css('color', '#333');
	}
});

// 모달창 띄우기
$(document).on('click', 'a.btn_search', function(event) {
	event.preventDefault();
	var target = $('#modal_' + $(this).attr('href').replace('#', ''));
	if ( target.is(':visible') ) target.hide();
	else target.css('display', 'flex').hide().fadeIn('fast');
});
// 모달창 닫기
$(document).on('click', '.modal_target_container h3 i, .modal_target_container .btn_close', function(event) {
	$('.modal_target').hide();
	return false;
});
// 모달창 닫기 - 예외
$(document).on('click', '.modal_target_container h3, .modal_target_wrapper', function(event) {
	event.stopImmediatePropagation();
});

// 카테고리 hover
$('.ctg_list > ul > li').hover(function(event) {
	$(this).children('ul').show();
}, function(){
	$(this).children('ul').hide();
});