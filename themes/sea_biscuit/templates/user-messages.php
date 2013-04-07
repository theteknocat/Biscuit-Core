<?php
if (!empty($user_messages)) {
	?><div id="biscuit-user-messages"><a id="user-messages-close" href="#close">Close</a><?php print $user_messages; ?></div>
	<script type="text/javascript">
		$(document).ready(function() {
			var close_timer = setTimeout("$('#biscuit-user-messages').fadeOut('fast',function() { $(this).remove(); });",12000);
			$('#user-messages-close').click(function() {
				clearTimeout(close_timer);
				$('#biscuit-user-messages').fadeOut('fast',function() { $(this).remove(); });
				return false;
			});
		});
	</script><?php
}
