<?php
/**
 * Code file for doing any last-minute tasks or preparations for rendering the page with template. This file is where you set any additional view vars needed by the template,
 * which can include any logic needed to determine whether or not certain things need rendering
 *
 * @author Peter Epp
 */
if (!Request::is_ajax()) {
	$Biscuit->set_view_var('login_link',$Biscuit->ExtensionNavigation()->login_link());
	$Biscuit->set_view_var('list_menu',$Biscuit->ExtensionNavigation()->render_list_menu());
	$Biscuit->set_view_var('breadcrumbs',$Biscuit->ExtensionNavigation()->render_bread_crumbs());
	$Biscuit->set_view_var('text_mainmenu',$Biscuit->ExtensionNavigation()->render_text_mainmenu());
	$Biscuit->set_view_var('copyright_notice', Crumbs::copyright_notice('Peter Epp'));
}
?>