<?php
$allowed_html = "p[class|style],
				strong,
				b,
				i,
				em,
				h1[class|style],
				h2[class|style],
				h3[class|style],
				h4[class|style],
				br,
				hr,
				a[href|title|class|style|target],
				ul[class|style],
				ol[class|style],
				li[class|style],
				dl[class|style],
				dt[class|style],
				dd[class|style],
				span[class|style],
				img[alt|src|width|height|border|class|style],
				sup,
				sub,
				table[width|cellpadding|cellspacing|border|class|style],
				tr[class|style],
				td[width|align|valign|style|class],
				blockquote[class|style]";

	if ($PageContentManager->user_can_edit($Biscuit->page_id) || $PageContentManager->user_can_manage_pages()) {
?>
		<div class="controls">
			<?php if ($PageContentManager->user_can_manage_pages()) {
				?><a href="<?php echo $PageManager->url('index') ?>" class="rightfloat">Manage Pages</a><?php
			} ?><a href="<?php echo $PageContentManager->url("edit",$Biscuit->page_id)?>" class="rightfloat">Edit Page</a>
		Administration</div>
<?php
	}
	echo H::purify_html($PageContentManager->page_data->content(),array('allowed' => $allowed_html, 'css_allowed' => array('width','height','text-align','text-decoration','padding','padding-left','margin','border')));
?>
