// 일시를 날짜와 시간으로 구분해서 전달 + 지정한 날짜의 요일에 따라서 반복 옵션 모달창의 요일 설정 체크 및 해제 방지 + 지정한 날짜에 따라 음력일 표시
var week_name = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
var week_name_kr = ['일', '월', '화', '수', '목', '금', '토'];
$(document).on('change', '.insert_start, .insert_end', function() {
	var value = $(this).val().replace(/[^0-9]/g, '');
	var date = value.substr(0, 8);
	var time = value.substr(8, 4);

	var year = value.substr(0, 4);
	var month = value.substr(4, 2);
	var day = value.substr(6, 2);
	var week = new Date(year, month - 1, day, 0, 0, 0, 0);
	week = week_name[week.getDay()];

	var lunar_date = solarToLunar(year, month, day);

	if ( $(this).attr('class').indexOf('insert_start') != -1 ) {
		$('#start_date').val(date);
		$('#start_time').val(time);

		var week_info = getWeekOrderAndDay(year + '-' + month + '-' + day);
		$('[name="week_order[]"]').removeAttr('onclick');
		$('[name="week_order[]"]#order_' + week_info.order).prop('checked', true).attr('onclick', 'return false;');
		$('[name="weekdays[]"]').removeAttr('onclick');
		$('[name="weekdays[]"]#weekday_' + week_info.weekday).prop('checked', true).attr('onclick', 'return false;');

		var lunar_start = lunar_date;
		$('.recur_lunar_start .lunar_year').text(lunar_date.year);
		$('.recur_lunar_start .lunar_leap').text((lunar_date.leapMonth ? '(윤)' : ''));
		$('.recur_lunar_start .lunar_month').text(lunar_date.month + '월');
		$('.recur_lunar_start .lunar_day').text(lunar_date.day + '일');
	} else if ( $(this).attr('class').indexOf('insert_end') != -1 ) {
		$('#end_date').val(date);
		$('#end_time').val(time);

		if ( date != $('#start_date').val() ) {
			if ( lunar_date.month != $('.recur_lunar_start .lunar_month').text().replace('월', '') ) {
				$('.recur_lunar_end .lunar_year').text(lunar_date.year);
				$('.recur_lunar_end .lunar_leap').text('~' + (lunar_date.leapMonth ? '(윤)' : ''));
				$('.recur_lunar_end .lunar_month').text(lunar_date.month + '월');
				$('.recur_lunar_end .lunar_day').text(lunar_date.day + '일');
			} else {
				$('.recur_lunar_end .lunar_year').text('');
				$('.recur_lunar_end .lunar_leap').text('~');
				$('.recur_lunar_end .lunar_month').text('');
				$('.recur_lunar_end .lunar_day').text(lunar_date.day + '일');
			}
		} else {
			$('.recur_lunar_end .lunar_year').text('');
			$('.recur_lunar_end .lunar_leap').text('');
			$('.recur_lunar_end .lunar_month').text('');
			$('.recur_lunar_end .lunar_day').text('');
		}
	}
});

// 시작 및 종료의 datetime picker 설정
luxon.Settings.defaultLocale = 'ko';
var eventBus = new Vue();
$.each(['start', 'end'], function (i, v) {
	new Vue({
		el: '#insert_' + v,
		data: {
			defaultDateTime: $('#'+ v +'_date').val().replace(/(\d{4})(\d{2})(\d{2})/g, '$1-$2-$3') + 'T' + $('#'+ v +'_time').val().replace(/(\d{2})(\d{2})/g, '$1:$2'),
			minDatetime: ( v == 'end' ) ? $('#start_date').val().replace(/(\d{4})(\d{2})(\d{2})/g, '$1-$2-$3') + 'T' + $('#start_time').val().replace(/(\d{2})(\d{2})/g, '$1:$2') : '',
		},
		watch: {
			defaultDateTime(val) {
				var datetime = val.split('T');
				var date = datetime[0].replace(/\-/g, '');
				var time = datetime[1].substr(0, 5).replace(/\:/g, '');
				$($(this)[0].$el).find('.insert_' + v).attr('value', date + time).trigger('change');
				if ( v == 'start' ) eventBus.$emit('triggerEventBus', val);
			}
		},
		methods: {
			setDateTime: function() {
				eventBus.$emit('triggerEventBus', val);
			}
		},
		created: function() {
			if ( v == 'end' ) {
				var $end = this;
				eventBus.$on('triggerEventBus', function(val) {
					if ( $end.defaultDateTime && $end.defaultDateTime < val ) {
						$end.defaultDateTime = val;
					}
					$end.minDatetime = val;
				});
			}
		}
	});
});

// 종일 시간 설정 및 되돌리기
var $picker1 = $('.insert_start'), $picker2 = $('.insert_end');
var $replacement = $('.date_for_allday')
var new_date = [], picked = '', old_start = '', old_end = '';

$('#is_allday').on('change', function() {
	picked = $picker1.val().replace(/[^0-9]/g, '');
	new_date[0] = picked.substr(0, 4);
	new_date[1] = picked.substr(4, 2);
	new_date[2] = picked.substr(6, 2);

	if ( $(this).is(':checked') ) {
		old_start = $picker1.val();
		old_end = $picker2.val();

		$picker1.val(new_date[0] + '-' + new_date[1] + '-' + new_date[2] + ' 00:00').trigger('change');
		$picker2.val(new_date[0] + '-' + new_date[1] + '-' + new_date[2] + ' 23:59').trigger('change');

		$picker1.prop('disabled', true);
		$picker2.prop('disabled', true);

		$replacement.val(new_date[0] + '-' + new_date[1] + '-' + new_date[2]);
		$replacement.removeClass('hidden');
	} else {
		if ( old_start && old_end )
		{
			$picker1.val(old_start).trigger('change');
			$picker2.val(old_end).trigger('change');
		}

		$picker1.removeAttr('disabled');
		$picker2.removeAttr('disabled');

		$replacement.addClass('hidden');
	}
});

// 모달창 띄우기 + 반복 설정 설명문 연동
$(document).on('change', 'input[name="is_recurrence"]', function(event) {
	var target = $('#modal_recur');
	if ( $(this).is(':checked') ) {
		target.css('display', 'flex').hide().fadeIn('fast');
	} else {
		 target.hide();
		$(this).parent().siblings('.toggle_desc.is_set').removeClass('shown');
		$(this).parent().siblings('.toggle_desc.not_set').addClass('shown');
	}
});
// 모달창 탭 메뉴 => 요일탭:토일요일 선택 방지 + 요일탭:주별 대체 선택 + 정례탭|직접탭:일별 대체 선택
$(document).on('click', '.modal_target_tab', function() {
	$(this).siblings().removeClass('on');
	$(this).addClass('on');

	var target_id = $(this).children().attr('href');
	$('.modal_target_selector').slideUp();
	if ( target_id == '#recur_weekday' ) {
		$('#exception_saturday, #exception_sunday').prop('disabled', true);
		$('label[for="opt_prev_week"], label[for="opt_next_week"]').removeClass('hidden');
		$('label[for="opt_prev_day"], label[for="opt_next_day"]').addClass('hidden');
	} else {
		$('#exception_saturday, #exception_sunday').prop('disabled', false);
		$('label[for="opt_prev_day"], label[for="opt_next_day"]').removeClass('hidden');
		$('label[for="opt_prev_week"], label[for="opt_next_week"]').addClass('hidden');
	}
	$('.modal_target_selector' + target_id).slideDown();

	return false;
});
// 모달창 요일 설정시 토요일과 일요일은 선택 제외 경고
$(document).on('click', 'label[for="exception_saturday"], label[for="exception_sunday"]', function() {
	if ( $(this).children('input').is(':disabled') ) {
		alert('[요일 설정] 옵션은 "휴일"에만 적용합니다.');
	}
});
// 모달창에서 ENTER키 => submit 방지 + 모달창 설정 클릭 트리거
$(document).on('keydown', '#modal_recur input', function(event) {
	if ( event.keyCode === 13 || event.which === 13 ) {
		event.preventDefault();
		$('.modal_target_container .btn_submit').trigger('click');
	}
});
// 모달창 열렸을 때 ESC키 => 모달창 취소 클릭 트리거
$(document).on('keydown', function(event) {
	if ( $('#modal_recur').is(':visible') && (event.keyCode === 27 || event.which === 27) ) {
		$('.modal_target_container .btn_close').trigger('click');
    }
});
// 모달창 닫기 + 반복 설정 설명문 연동
$(document).on('click', '.modal_target_container h3 i, .modal_target_container .btn_submit, .modal_target_container .btn_close', function(event) {
	$('.modal_target').hide();
	if ( $(this).attr('id') == 'modal_recur' || $(this).closest('.modal_target').attr('id') == 'modal_recur' ) {
		if ( $(this).attr('class') == 'navi_btn btn_submit' ) {
			$('[id^="disp_recur_type_"]').hide();

			$('input[name="recur_type"]').val($('.modal_target_tab[class*="on"]').children('a').attr('href').replace('#recur_', ''));
			if ( $('input[name="recur_cycle"]').val() == '' ) {
				$('input[name="recur_cycle"]').val($('[class^="setup_cycle_"]').val());
			}

			if ( $('input[name="recur_type"]').val() == 'regular' && !$('input[name="recur_regular"]:checked').length ) {
				$('input[name="recur_regular"]#recur_regular_daily').prop('checked', true);
			}
			$('#disp_recur_regular').text($('input[name="recur_regular"]:checked').next().text());
			var orders = weeks = '';
			$('input[name="week_order[]"]:checked').each(function(i) {
				orders += (i == 0) ? '' : '/';
				orders += $(this).next().text();
			});
			$('input[name="weekdays[]"]:checked').each(function(i) {
				weeks += (i == 0) ? '' : '/';
				weeks += $(this).next().text();
			});
			$('#disp_recur_weekday').text(orders + ' ' + weeks);
			var lunarday_value = '';
			lunarday_value += $('.recur_lunar_start').parent().children('span').text() +
				$('.recur_lunar_start').children('.lunar_leap').text() +
				$('.recur_lunar_start').children('.lunar_month').text() +
				$('.recur_lunar_start').children('.lunar_day').text();
			lunarday_value += $('.recur_lunar_end').children('.lunar_leap').text() +
				$('.recur_lunar_end').children('.lunar_month').text() +
				$('.recur_lunar_end').children('.lunar_day').text();
			$('#disp_recur_lunar').text(lunarday_value);
			$('#disp_recur_cycle').text($('input[name="recur_cycle"]').val());
			$('#disp_recur_units').text($('.setup_unit').children('option:selected').text());

			var new_type = $('input[name="recur_type"]').val();
			$('#disp_recur_type_' + new_type).show();

			if ( $('[name="stop_date"]').val() != '' ) {
				var regex = /[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/;
				if ( regex.test($('[name="stop_date"]').val()) ) {
					var Ymd = $('[name="stop_date"]').val().split('-');
					$('#disp_stop_date').text(
						'\u00a0\u00a0|\u00a0\u00a0' +
						Ymd[0] + '년 ' + Ymd[1].replace(/(^0+)/, '') + '월 ' + Ymd[2].replace(/(^0+)/, '') + '일' +
						'에 중단'
					);
				} else {
					$('[name="stop_date"]').val('');
					$('#disp_stop_date').text('');
				}
			} else {
				$('#disp_stop_date').text('');
				if ( !$('[name="recur_freq"]').val() || $('[name="recur_freq"]').val() < 1 ) {
					$('[name="recur_freq"]').val(1);
				}
			}

			if ( Number($('[name="recur_freq"]').val()) > 0 ) {
				$('#disp_recur_freq_num').text($('[name="recur_freq"]').val());
				$('#disp_recur_freq').show();
			} else {
				$('#disp_recur_freq_num').text('');
				$('#disp_recur_freq').hide();
			}
			

			if ( !$('[name="exception_type[]"]:checked').length ) {
				$('#disp_exception_type').text('');
				$('#disp_exception_option').text('');
				$('#disp_exception_type').hide();
				$('#disp_exception_option').hide();
			} else {
				var type = '';
				$('[name="exception_type[]"]:checked').each(function(i) {
					if ( i == 0 ) {
						type = '\u00a0\u00a0|\u00a0\u00a0' + $(this).next().text();
						if ( i == $('[name="exception_type[]"]:checked').length - 1 ) {
							type += '은\u00a0';
						}
					} else {
						type += '/' + $(this).next().text();
						if ( i == $('[name="exception_type[]"]:checked').length - 1 ) {
							type += '은\u00a0';
						}
					}
				});
				$('#disp_exception_type').text(type);
				$('#disp_exception_option').text($('[name="exception_option"]:checked').next().text());
				$('#disp_exception_type').show();
				$('#disp_exception_option').show();
			}

			$('input[name="is_recurrence"]').parent().siblings('.toggle_desc.is_set').addClass('shown');
			$('input[name="is_recurrence"]').parent().siblings('.toggle_desc.not_set').removeClass('shown');
			return false;
		}
		else {
			$('input[name="is_recurrence"]:checked').prop('checked', false);
		}
	}
	return false;
});
// 모달창 닫기 : 예외
$(document).on('click', '.modal_target_container h3, .modal_target_container ul, .modal_target_wrapper', function(event) {
	event.stopImmediatePropagation();
});

// 반복 설정 : 정례 설정 => 직접 설정에도 전달
$(document).on('change', '[name="recur_regular"]', function() {
	var key = ( $(this).val() == 'daily' ) ? 'day' : $(this).val().slice(0, -2);
	$('[class^="setup_cycle_"]').addClass('hidden');
	$('.setup_unit').val(key);
	$('[class^="setup_cycle_'+ key +'"]').val('1').removeClass('hidden').trigger('change');
});
// 반복 설정 : 반복 주기 선택 => 입력값 전달
$(document).on('change', '[class^="setup_cycle_"]', function() {
	$('input[name="recur_cycle"]').val($(this).val());
	// 반복 설정 : 직접 설정 => (반복주기가 1이면) 정례 설정에도 전달
	if ( $(this).val() == '1' ) {
		var key = ( $('.setup_unit').val() == 'day' ) ? 'daily' : $('.setup_unit').val() + 'ly';
		if ( $('[name="recur_regular"]:checked').val() != key ) {
			$('[name="recur_regular"]:radio[value="'+ key +'"]').prop('checked', true);
		}
	}
});
// 반복 설정 : 반복 단위 선택 => 반복 주기 옵션도 연동
$(document).on('change', '.setup_unit', function() {
	$(this).siblings().addClass('hidden');
	$(this).siblings('.setup_cycle_' + $(this).val().toLowerCase()).removeClass('hidden').trigger('change');
});

// 반복 설정 : 반복 종료일 설정 + 최대값(내년 12월 31일) 설정
new Vue({
	el: "#setup_stop",
	data: {
		defaultDateTime: $('#stop_date').val(),
		minDatetime: $('#start_date').val().replace(/(\d{4})(\d{2})(\d{2})/g, '$1-$2-$3') + 'T00:00',
	},
	watch: {
		defaultDateTime(val) {
			var stop_date = val.split('T')[0];
			$('#stop_date').val(stop_date);
			if ( (Number($('[name="recur_freq"]').val()) < 1 || $('[name="recur_freq"]').val() == '') && $('#stop_date').val() == '' ) {
				$('.modal_target_option_cap').addClass('shown');
			} else {
				$('.modal_target_option_cap').removeClass('shown');
			}
		}
	},
	methods:{
		resetDate(event) {
			this.defaultDateTime = '';
		}
	},
	created: function() {
		var $stop = this;
		eventBus.$on('triggerEventBus', function(val) {
			if ( $stop.defaultDateTime && $stop.defaultDateTime < val ) {
				$stop.defaultDateTime = val;
			}
			$stop.minDatetime = val;
		});
	}
});
// 반복 설정 : 반복 제외 설정 유형 체크 => 제외 옵션 선택 라디오 버튼 출력
$(document).on('change', '[name="exception_type[]"]', function() {
	var content = '';
	if ( !$('[name="exception_type[]"]:checked').length ) {
		$('[name="exception_option"]:checked').prop('checked', false);
		$('.modal_additive_option').slideUp();
	}
	else
	{
		if ( !$('[name="exception_option"]:checked').length ) {
			$('[name="exception_option"]').eq(0).prop('checked', true);
		}
		$('.modal_additive_option').slideDown();
	}
});
// 반복 횟수 및 종료일 입력값 없을 때 주석 출력
$(document).on('input blur', '[name="recur_freq"]', function() {
	if ( (Number($(this).val()) < 1 || $(this).val() == '') && $('[name="stop_date"]').val() == '' ) {
		$('.modal_target_option_cap').addClass('shown');
	} else {
		$('.modal_target_option_cap').removeClass('shown');
	}
});