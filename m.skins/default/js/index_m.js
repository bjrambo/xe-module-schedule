// 모달 창 스크롤 메뉴에서 선택 아이템에 포커싱(스크롤 이동)
$('#modal_search').css({
	'left' : '100%',
	'display' : 'flex'
});
$('#modal_search .modal_item_picker').each(function() {
	setElementToMiddle($(this), $(this).children('.selected'), 0);
});
$('#modal_search').css({
	'left' : 0,
	'display' : 'none'
});
// 로드시 카테고리 셀렉트 옵션을 조정 + 선택 아이템에 포커싱(스크롤 이동)
var current_category_srl = $('#modal_category').data('current_category_srl');
if ( current_category_srl ) {
	if ( current_category_srl == $('.modal_item_picker[rel="category_1st"] > .selected').data('category_srl') ) {
		$('.modal_item_picker[rel="category_2nd"]').children().each(function() {
			if ( $(this).data('parent_srl') == 0 || $(this).data('parent_srl') == current_category_srl ) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	} else if ( current_category_srl == $('.modal_item_picker[rel="category_2nd"] > .selected').data('category_srl') ) {
		var current_parent_srl = $('.modal_item_picker[rel="category_2nd"] > .selected').data('parent_srl');
		$('.modal_item_picker[rel="category_2nd"]').children().each(function() {
			if ( $(this).data('parent_srl') == 0 || $(this).data('parent_srl') == current_parent_srl ) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	}
	$('#modal_category').css({
		'left' : '100%',
		'display' : 'flex'
	});
	$('#modal_category .modal_item_picker').each(function() {
		setElementToMiddle($(this), $(this).children('.selected'), 0);
	});
	$('#modal_category').css({
		'left' : 0,
		'display' : 'none'
	});
}

// 모달 창 스크록 메뉴에서 년월 선택
$(document).on('click', '.modal_item', function() {
	var rel = $(this).parent().attr('rel');
	if ( rel == 'search_year' ) {
		$('[name="s_year"]').val($(this).text().replace(/[^0-9]+/g, ''));
	} else if ( rel == 'search_month' ) {
		$('[name="s_month"]').val(('0' + ($(this).text().replace(/[^0-9]+/g, ''))).slice(-2));
	}

	$(this).parent().children('.selected').removeClass('selected');
	$(this).addClass('selected');

	setElementToMiddle($(this).parent(), $(this), 200);
});

// 1차 분류 선택에 따라 2차 분류 연동
$(document).on('click', '.modal_item_picker[rel="category_1st"]', function() {
	var given = $(this).children('.selected').data('category_srl');
	if ( given == 0 ) {
		$(this).next().children().show();
		$(this).next().children('.selected').removeClass('selected');
		$(this).next().children().eq(0).addClass('selected');
	} else {
		$(this).next().children().each(function() {
			if ( $(this).data('parent_srl') == 0 || $(this).data('parent_srl') == given ) {
				if ( $(this).data('parent_srl') == 0 ) {
					$(this).parent().children('.selected').removeClass('selected');
					$(this).addClass('selected');
				}
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	}
	$('#modal_category .modal_item_picker').each(function() {
		setElementToMiddle($(this), $(this).children('.selected'), 0);
	});
});
// 2차 분류 선택에 따라 1차와 2차 분류도 연동
$(document).on('click', '.modal_item_picker[rel="category_2nd"]', function() {
	var given = $(this).children('.selected').data('parent_srl');
	if ( given == 0 ) {
		return false;
	} else {
		$('.modal_item_picker[rel="category_1st"]').children('.selected').removeClass('selected');
		$('.modal_item_picker[rel="category_1st"]').children('[data-category_srl="'+ given +'"]').addClass('selected');
		$(this).children().each(function() {
			if ( $(this).data('parent_srl') == 0 || $(this).data('parent_srl') == given ) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	}
	$('#modal_category .modal_item_picker').each(function() {
		setElementToMiddle($(this), $(this).children('.selected'), 0);
	});
});
// 카테고리 이동
$(document).on('click', '#modal_category .modal_target_container .btn_submit', function(event) {
	var $first = $('.modal_item_picker[rel="category_1st"]');
	var $second = $('.modal_item_picker[rel="category_2nd"]');

	if ( $second.children('.selected').length > 0 && $second.children('.selected').attr('rel') != '0' ) {
		location.href = $second.children('.selected').attr('rel');
	} else {
		if ( $first.children('.selected').length > 0 ) {
			location.href = $first.children('.selected').attr('rel');
		} else {
			return false;
		}
	}
});

// 컨테이너에서 선택 아이템으로 포커싱(스크롤 이동)
function setElementToMiddle(container, item, delay) {
	if ( item.length ) {
		container.animate({
			scrollTop: item.offset().top - container.offset().top + container.scrollTop() - (container.outerHeight()/2) + (item.outerHeight()/2)
		}, delay);
	}
}