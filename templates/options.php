<script type="text/javascript">
// <![CDATA[

var cleanup_media_attachments = null;

jQuery(document).ready(function($) {

	$('body').on('click', '#mediacleanup-toggle', function(event) {

		event.preventDefault();

		var cleanup_list = $('#mediacleanup-list');
		
		var total_checked = $('input[type="checkbox"]:checked', cleanup_list).length;
		var total_unchecked = $('input[type="checkbox"]', cleanup_list).length - total_checked;

		if (total_unchecked === 0) {
			$('input[type="checkbox"]', cleanup_list).prop('checked', false);
		} else if (total_checked === 0) {
			$('input[type="checkbox"]', cleanup_list).prop('checked', true);
		} else if (total_checked > total_unchecked) {
			$('input[type="checkbox"]', cleanup_list).prop('checked', true);
		} else {
			$('input[type="checkbox"]', cleanup_list).prop('checked', false);
		}

	});

	$('body').on('click', '[data-action="mediacleanup"]', prepare_cleanup);

	function prepare_cleanup() {

		var cleanup_button = $('[data-action="mediacleanup"]');

		if (cleanup_button.hasClass('disabled')) {
			return;
		}

		$('#action-log').show();

		$('#progress-bar').show();
		$('#progress-bar .progress').css('width', 0);

		$('#action-log').prepend('<li>Scanning for unused media</li>');

		cleanup_button.addClass('disabled');

		if (cleanup_button.hasClass('confirm')) {
			cleanup_media(0);
			return;
		}

		var cleanup_list = $('#mediacleanup-list');
		var checked = $('input[type="checkbox"]:checked');

		var rules = [];

		$(checked).each(function() {
			rules.push($(this).val());
		});

		$.ajax({
			url: '<?php echo admin_url('admin-ajax.php'); ?>',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'prepare_cleanup',
				rules: rules
			},
			success: function(result) {

				var totalAttachments = Object.keys(result.attachments).length;

				if (totalAttachments) {

					cleanup_media_attachments = result.attachments;

					cleanup_button.addClass('confirm');

					$('#action-log').prepend('<li>Found ' + totalAttachments + ' attachment' + (totalAttachments > 1 ? 's' : '') + '</li>');

					$('#ajax-response').html('<h4 style="margin-top:30px;">' + totalAttachments + ' attachment' + (totalAttachments > 1 ? 's' : '') + ' found</h4><p>Click \'Cleanup Media\' again to confirm removal.</p><p><strong>Warning:</strong> This will delete the media from your website and cannot be undone.</p>');

				} else {
					$('#action-log').prepend('<li>No unused media found</li>');
					$('#ajax-response').html('<p>No unused media found.</p>');
				}

			},
			complete: function() {
				cleanup_button.removeClass('disabled');
			}
		});

	}

	function cleanup_media(index) {

		if (typeof cleanup_media_attachments[index] == 'undefined') {
			cleanup_complete();
			return;
		}

		var attachment = cleanup_media_attachments[index];

		$('#action-log').prepend('<li>Deleting attachment #' + attachment.ID + '</li>');

		$.ajax({
			url: '<?php echo admin_url('admin-ajax.php'); ?>',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'delete_attachment',
				attachment_id: attachment.ID
			},
			success: function(result) {

				if (result.success === 'true') {
					$('#action-log').prepend('<li>Attachment #' + attachment.ID + ' deleted</li>');
				} else {
					$('#action-log').prepend('<li>Failed to delete attachment #' + attachment.ID + '</li>');
				}

				var percentageCompleted = ((index + 1) / Object.keys(cleanup_media_attachments).length) * 100;

				$('#progress-bar .progress').css('width', percentageCompleted + '%');

			},
			error: function() {
				$('#action-log').prepend('<li>Failed to delete attachment #' + attachment.ID + '</li>');
			},
			complete: function() {
				cleanup_media(index + 1);
			}
		});

	}

	function cleanup_complete() {

		$('#ajax-response').html('');

		$('#action-log').prepend('<li>Media Cleanup process complete</li>');

		$('[data-action="mediacleanup"]').removeClass('confirm').removeClass('disabled');

	}

});

// ]]>
</script>
<div class="wrap">
	<div class="half">
		<h1>Media Cleanup</h1>
		<p>To clean up the media on your website, select from the list below. Any media found in the selected rulesets will <strong>not be</strong> deleted.</p>
		<form action="" method="post">
			<a href="javascript:;" id="mediacleanup-toggle">Toggle all</a>
			<ul id="mediacleanup-list">
				<li><label><input type="checkbox" name="mediacleanup[]" checked="checked" value="featured-images"> Featured Images</label></li>
				<li><label><input type="checkbox" name="mediacleanup[]" checked="checked" value="post-content"> Post Content</label></li>
				<?php if (is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('advanced-custom-fields-pro/acf.php')) { ?>
					<li><label><input type="checkbox" name="mediacleanup[]" checked="checked" value="acf-fields"> ACF Fields</label></li>
				<?php } else if (file_exists(__DIR__ . '/../../advanced-custom-fields') || file_exists(__DIR__ . '/../../advanced-custom-fields-pro')) { ?>
					<li><label><input type="checkbox" name="mediacleanup[]" checked="checked" value="acf-fields"> ACF Fields <em>(plugin inactive)</em></label></li>
				<?php } ?>
				<li><label><input type="checkbox" name="mediacleanup[]" checked="checked" value="theme-files"> Theme Files <em>(<?php echo wp_get_theme()->get('Name'); ?>)</em><br><small style="margin-left:25px;"><strong>Note:</strong> This only checks if the attachments full URL exists in a themes template file</small></label></li>
			</ul>
			<input data-action="mediacleanup" type="button" class="button" name="ajax_thumbnail_rebuild" id="ajax_thumbnail_rebuild" value="Cleanup Media">
			<div id="ajax-response"></div>
		</form>
	</div>
	<div class="half">
		<ul id="action-log"></ul>
		<div id="progress-bar">
			<div class="progress"></div>
		</div>
	</div>
</div>
<style type="text/css">
.half {
	width: 50%;
	float: left;
}

#action-log {
	display: none;
	box-sizing: border-box;
	height: 300px;
	overflow-y: scroll;
	font-family: "Lucida Console", sans-serif;
	font-size: 12px;
	list-style-type: none;
	margin: 50px 0 0;
	padding: 20px 0;
	background-color: #ffffff;
	border: 1px solid #e3e3e3;
	-webkit-box-shadow: 1px 1px 2px 0 rgba(0, 0, 0, 0.05);
	box-shadow: 1px 1px 2px 0 rgba(0, 0, 0, 0.05);
	-webkit-border-radius: 6px 6px 0 0;
	   -moz-border-radius: 6px 6px 0 0;
	    -ms-border-radius: 6px 6px 0 0;
	     -o-border-radius: 6px 6px 0 0;
	        border-radius: 6px 6px 0 0;
}

#action-log li {
	box-sizing: border-box;
	width: 100%;
	display: inline-block;
	margin: 0;
	padding: 5px 25px;
}

#action-log li:nth-child(2n) {
	background-color: #f5f5f5;
}

#progress-bar {
	position: relative;
	width: 100%;
	height: 22px;
	background-color: #eaeaea;
	overflow: hidden;
	-webkit-border-radius: 0 0 6px 6px;
	   -moz-border-radius: 0 0 6px 6px;
	    -ms-border-radius: 0 0 6px 6px;
	     -o-border-radius: 0 0 6px 6px;
	        border-radius: 0 0 6px 6px;
}

#progress-bar .progress {
	position: absolute;
	top: 0;
	left: 0;
	width: 0;
	height: 100%;
	background-color: #4bb543;
	-webkit-transition: width .25s linear;
	   -moz-transition: width .25s linear;
	    -ms-transition: width .25s linear;
	     -o-transition: width .25s linear;
	        transition: width .25s linear;
}
</style>