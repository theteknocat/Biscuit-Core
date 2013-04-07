<?php
class PageAliasFactory extends ModelFactory {
	/**
	 * Update all existing alias that point to the old slug with the new slug
	 *
	 * @param string $old_slug 
	 * @param string $new_slug 
	 * @return void
	 * @author Peter Epp
	 */
	public function update($old_slug,$current_slug,$sub_pages = null) {
		if ($old_slug == null) {
			// An NULL old slug means a page was deleted so we want to remove all aliases for it and any of it's child pages
			DB::query("DELETE FROM `page_aliases` WHERE `current_slug` = ? OR `current_slug` LIKE ?",array($current_slug,$current_slug.'/%'));
		} else {
			// First update all existing aliases that match the exact old and new slugs:
			DB::query("UPDATE `page_aliases` SET `current_slug` = ? WHERE `current_slug` = ?",array($current_slug,$old_slug));
			// Now add a new alias:
			DB::query("INSERT INTO `page_aliases` SET `old_slug` = ?, `current_slug` = ?",array($old_slug,$current_slug));
			// If an array of sub-pages were provided, updated all aliases for the sub-pages:
			if (!empty($sub_pages)) {
				foreach ($sub_pages as $index => $sub_page) {
					$old_subpage_slug = $sub_page->slug();
					$subpage_slug = substr($old_subpage_slug,strlen($old_slug)+1);	// Everything after the current new slug plus a "/"
					$new_subpage_slug = $current_slug."/".$subpage_slug;
					// Update any existing aliases:
					DB::query("UPDATE `page_aliases` SET `current_slug` = ? WHERE `current_slug` = ?",array($current_slug,$old_subpage_slug));
					// Add a new alias:
					DB::query("INSERT INTO `page_aliases` SET `old_slug` = ?, `current_slug` = ?",array($old_subpage_slug,$new_subpage_slug));
				}
			}
			// Ensure that any aliases where the old and current slugs match are removed, to accommodate cases where a page might get moved/renamed
			// back to a previous state
			DB::query("DELETE FROM `page_aliases` WHERE `old_slug` = `current_slug`");
		}
	}
}
?>