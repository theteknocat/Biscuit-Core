<!DOCTYPE html>
<html lang="<?php echo $lang ?>">
	<head>
	<?php print $header_tags; ?>

	<?php print $header_includes; ?>

	</head>
	<body id="<?php echo $body_id ?>" class="locale-<?php echo $locale ?>">
		<!-- Start Header Container -->
		<h1><a href="/" title="<?php echo __('Return to Home Page'); ?>"><?php echo __(SITE_TITLE); ?></a></h1>
		<?php
		include('user-messages.php');
		$menu_class = '';
		if (!empty($search_form)) {
			?>
		<div id="search-widget">
			<?php print $search_form; ?>
		</div>
			<?php
			$menu_class = 'with-search';
		}
		?>
		<?php print $list_menu; ?>
		<?php
		if (!empty($breadcrumbs)) {
			?><div id="breadcrumbs"><?php print $breadcrumbs ?></div><?php
		}
		?>
		<h2><?php print $page_title; ?></h2>
		<?php print $page_content; ?>
		<p><?php print $copyright_notice; ?> &bull; <?php echo sprintf(__('Powered By Biscuit %s'),(string)Biscuit::version()); ?></p>
		<?php
			print $footer;
		?>
	</body>
</html>
