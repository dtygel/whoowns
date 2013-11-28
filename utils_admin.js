jQuery(document).ready(function($) {
	$(".whoowns_auto_label").autocomplete({
	    //define callback to format results
	    source: function(req, add){
			//pass request to server
			var params = {
				action: 'whoowns_autocomplete',
				term: req.term
			};
			$.getJSON(ajaxurl, params, function(data) {
				//pass array to callback
				add(data);
            });
		},

       	minLength: 3,
       	
       	selectFirst: true, 

		focus: function( event, ui ) {
			$(this).val(ui.item.label);
			$(this).next(".whoowns_auto_id").val(ui.item.value);
            return false;
		},

        select: function(e, ui) {
            var label = ui.item.label,
            	 span = $("<span>").text(label),
                 a = $("<a>").addClass("remove").attr({
	                href: "javascript:",
	                title: "Remove " + label
                }).text("x").appendTo(span);
			span.appendTo(this);
            $(this).val(label);
			$(this).next(".whoowns_auto_id").val(ui.item.value);
			return false;
            },
        change: function(e, ui) {
        	// If the user didn't select any item from the list, the item will be erased
			if ( !ui.item ) {
				$(this).val("");
				$(this).next(".whoowns_auto_id").val("");
			}
		}
	});
});

//Delete files through ajax
function whoowns_file_delete(file_name, post_id, element_id) {
	//Is the user sure that he/she wants to delete the file?
	if (confirm(ajax_object.delete_confirmation.replace('{file}',file_name))) {
		//Let's delete the file!
		var params = {
			action: 'whoowns_delete_file',
			file_name: file_name,
			element_id: element_id, 
			post_id: post_id
		};
		jQuery.post(ajax_object.ajax_url, params, function(response) {
			// I only do the alert if the file deletion did not work
			if (response)
				alert(response);
			// Hide file from the list:
			jQuery("#"+params.element_id).hide();
		});
	}
}

//Show/hide element
function whoowns_toggle(toggler,target,msg1,msg2) {
	jQuery("#"+target).toggle();
	var content = (jQuery("#"+toggler).html()==msg1) 
		? msg2
		: msg1;
	jQuery("#"+toggler).html(content);
}
