<?php
/**
 * Biscuit Template. You can use this as the starting point for new sites.
 *
 * If using the SiteSearch module, ensure that you put a class of "indexable-content" on the element containing the content you want indexed. Note that only the first
 * element with a class of "indexable-content" will get indexed.
 *
 * @author Peter Epp
 */
?>
<!DOCTYPE html>
<html lang="<?php echo $lang ?>">
	<head>
	<?php print $header_tags; ?>

	<?php print $header_includes; ?>

	</head>
	<body id="<?php echo $body_id ?>" class="locale-<?php echo $locale ?>">
		<!-- Start Header Container -->
		<div id="header">
			<div id="banner">
				<h1><a href="/" title="<?php echo __('Return to Home Page'); ?>"><?php echo __("Biscuit - The Rapid Application Development Framework") ?></a></h1>
				<div id="tab-bar">
					<?php
					if (!empty($tab_menu)) {
						print $tab_menu;
					}
					print $login_link;
					?>
				</div>
				<?php
				include('user-messages.php');
				?>
			</div>
		</div>
		<!-- End Header -->
		<!-- Start Main Body -->
		<div id="body-wrap">
			<div id="body-top"></div>
			<div id="left-bar">
				<?php
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
				<div id="main-menu" class="<?php echo $menu_class; ?>">
					<?php print $list_menu; ?>
				</div>
			</div>
			<div id="body" class="indexable-content">
				<?php
				if (!empty($breadcrumbs)) {
					?><div id="breadcrumbs"><?php print $breadcrumbs ?></div><?php
				}
				?>
				<h2><?php print $page_title; ?></h2>
				<?php print $page_content; ?>
			</div>
			<div class="clearance">&nbsp;</div>
		</div>
		<!-- End Main Body -->
		<!-- Start Page Footer -->
		<div id="footer">
			<div class="menu"><?php print $text_mainmenu; ?></div>
			<div id="copyright"><?php print $copyright_notice; ?> &bull; <?php echo sprintf(__('Powered By Biscuit %s'),(string)Biscuit::version()); ?></div>
		</div>
		<!-- End Page Footer -->
		<?php
			print $footer;
		?>
	</body>
</html>
