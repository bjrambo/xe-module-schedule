// 스와이프로 달력 페이지 넘기기 +  로딩 이미지 출력
var $calendar = Hammer(document.querySelector('.schedule_monthly'));
$calendar.on('swipeleft', function() {
	$('.navi_loading').removeClass('hidden');
	location.href = $('.navi_btn.to_next').attr('href');
});
$calendar.on('swiperight', function() {
	$('.navi_loading').removeClass('hidden');
	location.href = $('.navi_btn.to_prev').attr('href');
});

// 달력 페이지 넘김 시 로딩 이미지 출력
$(document).on('click', '.to_prev, .to_next, .navi_fr .navi_btn, .ctg_list a, .modal_target .btn_submit', function() {
	$('.navi_loading').removeClass('hidden');
});