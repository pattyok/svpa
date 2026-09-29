/*------------------------
Backend related javascript
------------------------*/
(function( $ ) {
	'use strict';

	$(document).ready(function(){
		$(document).on('click', '.btn_salesforce_manual_pull', function() {
			if (!$(this).hasClass('disabled')) {
				//$(this).addClass('disabled');
				$('i', this).addClass('fa-spin');
				var wordpress_object = 'tribe_events';
				var salesforce_id = $("input[name='sf-manual-pull-salesforce-id']").val();
				var update_type = $("input[name='sf-manual-pull-type']:checked").val();
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {action: 'pull_from_salesforce', wordpress_object: wordpress_object, salesforce_id: salesforce_id, update_type: update_type}
					}).done( function( msg ) {
						console.log(msg);
						var userMsg = '';
						$('.btn_salesforce_manual_pull').removeClass('disabled');
						if (msg.success) {
							userMsg = 'Success the Salesforce Object was successfully synced to WordPress.';
						} else {
							userMsg = 'Your file could not be processed at this time, we encountered the following errors: ' + JSON.stringify(msg.data);
							error = true;
						}
						$('.manual-pull-status').text(userMsg);
					});
		}

		});
});

})( jQuery );

