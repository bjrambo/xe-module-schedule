/* complete tp insert schedule */
function completeScheduleInserted(ret_obj) {
	var error = ret_obj.error;
	var message = ret_obj.message;
	var mid = ret_obj.mid;
	var schedule_srl = ret_obj.schedule_srl;
	var category_srl = ret_obj.category_srl;

	if ( ret_obj.redirect_url ) {
		redirect(ret_obj.redirect_url);
	} else {
		var url;
		if ( !schedule_srl ) {
			url = current_url.setQuery('mid', mid).setQuery('act', '');
		} else {
			url = current_url.setQuery('mid', mid).setQuery('schedule_srl', schedule_srl).setQuery('act', '');
		}
		if ( category_srl ) {
			url = url.setQuery('category', category_srl);
		}
		redirect(url);
	}
}

function getScheduleList() {
	var scheduleListTable = jQuery('#scheduleListTable');
	var cartList = [];
	scheduleListTable.find(':checkbox[name=cart]').each(function(){
		if ( this.checked ) cartList.push(this.value); 
	});

    var params = {schedule_srls : cartList.join(',')};

	exec_json('schedule.procScheduleGetList', params, completeGetScheduleList);
}

function completeGetScheduleList(ret_obj) {
	var htmlListBuffer = '';
	var statusNameList = {'STANDBY' : xe.lang.status_standby, 'PUBLIC' : xe.lang.status_public};
	if ( ret_obj.schedule_list == null ) {
		htmlListBuffer = '<tr>' +
							'<td colspan="3" style="text-align:center;">'+ret_obj['message']+'</td>' +
						'</tr>';
	} else {
		var schedule_list = ret_obj.schedule_list;
		if ( !jQuery.isArray(schedule_list) ) {
			schedule_list = [schedule_list];
		}
		for ( var x in schedule_list ) {
			var objSchedule = schedule_list[x];
			htmlListBuffer += '<tr>' +
								'<td class="title">'+ objSchedule.variables.title +'</td>' +
								'<td class="nowr">'+ objSchedule.variables.nick_name +'</td>' +
								'<td class="nowr">'+ statusNameList[objSchedule.variables.status] +'</td>' +
							'</tr>'+
							'<input type="hidden" name="cart[]" value="'+objSchedule.schedule_srl+'" />';
		}
		jQuery('#selectedScheduleCount').html(schedule_list.length);
	}
	jQuery('#scheduleManageListTable>tbody').html(htmlListBuffer);
}

function checkSearch(form) {
	if ( form.search_target.value == '') {
		alert(xe.lang.msg_empty_search_target);
		return false;
	}
	if ( form.search_keyword.value == '' ) {
		alert(xe.lang.msg_empty_search_keyword);
		return false;
	}
}

function completeSearch(ret_obj, response_tags, params, fo_obj) {
	if ( ret_obj.message == 'success' )
	{
		fo_obj.submit();
	}
}