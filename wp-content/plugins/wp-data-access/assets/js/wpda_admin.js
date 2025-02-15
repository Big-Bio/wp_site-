function wpda_show_table_actions( schema_name, table_name, rownum, wpnonce, dbo_type, loading ) {
    jQuery('#wpda_admin_menu_actions_' + table_name).toggle();
    wpda_toggle_row_actions(table_name, rownum);
    if (jQuery('#wpda_admin_menu_actions_' + table_name).html()===loading) {
        url = location.pathname + '?action=show_table_actions&schema_name=' + schema_name + '&table_name=' + table_name;
        jQuery.ajax({
            method: 'GET',
            url: url,
            data: { _wpnonce: wpnonce, dbo_type: dbo_type }
        }).done(
            function(msg) {
                jQuery('#wpda_admin_menu_actions_' + table_name).html(msg);
            }
        );
    }
}

function wpda_toggle_row_actions( table_name, rownum ) {
    if (jQuery('#wpda_admin_menu_actions_' + table_name).is(":visible")) {
        jQuery("#rownum_" + rownum + " td div").removeClass("row-actions");
	} else {
        jQuery("#rownum_" + rownum + " td div").addClass("row-actions");
	}
}

function wpda_list_table_favourite( schema_name, table_name )  {
	if (jQuery('#span_favourites_'+ table_name).hasClass('dashicons-star-empty')) {
		action = 'add_favourite';
	} else {
		action = 'rem_favourite';
	}
	url = location.pathname + '?action=' + action + '&schema_name=' + schema_name + '&table_name=' + table_name;
	jQuery.ajax({
		method: 'GET',
		url: url
	}).done(
		function (msg) {
			if (msg === '1') {
				if (jQuery('#span_favourites_' + table_name).hasClass('dashicons-star-empty')) {
					jQuery('#span_favourites_' + table_name)
					.removeClass('dashicons-star-empty')
					.addClass('dashicons-star-filled')
					.prop('title', 'Remove from favourites');
				} else {
					jQuery('#span_favourites_' + table_name)
					.removeClass('dashicons-star-filled')
					.addClass('dashicons-star-empty')
					.prop('title', 'Add to favourites');
				}
				if (jQuery('#wpda_main_favourites_list').val()!=='') {
                    jQuery("#wpda_main_form :input[name='action']").val('-1');
                    jQuery("#wpda_main_form :input[name='action2']").val('-1');
					jQuery('#wpda_main_form').submit();
				}
			} else {
				alert('Adding to favourites failed!');
			}
		}
	);
}

function wpda_show_notice( value ) {
    if (
    	value==='bulk-delete' ||
		value==='bulk-drop' ||
		value==='bulk-truncate'
	) {
        return showNotice.warn();
    }
}

function wpda_action_button() {
    action1 = jQuery("#wpda_main_form :input[name='action']").val();
    if (action1!=="-1") {
		return wpda_show_notice(action1);
    }
    action2 = jQuery("#wpda_main_form :input[name='action2']").val();
    if (action2!=="-1") {
        return wpda_show_notice(action2);
    }
    return true;
}