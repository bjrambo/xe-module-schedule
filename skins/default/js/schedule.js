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

// 스케줄 발행 상태 수정
$(document).on('click', '.modal_target_commander a.btn_submit', function(event) {
	doCallModuleAction('schedule', 'procScheduleStatusUpdate', current_url.getQuery('schedule_srl'));
	return false;
});