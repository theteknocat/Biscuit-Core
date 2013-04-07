<?php
/**
 * Handle session file storage
 *
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: session_store_file.php 14744 2012-12-01 20:50:43Z teknocat $
 */
class SessionStoreFile {
	/**
	 * Initialize the session file storage engine
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function init() {
		$session_dir = SITE_ROOT.'/var/sessions';
		if (Crumbs::ensure_directory($session_dir)) {
			$htaccess_file = $session_dir.'/.htaccess';
			if (!file_exists($htaccess_file)) {
				file_put_contents($htaccess_file, "deny from all\n");
			}
			session_save_path($session_dir);
			$random_num = rand(1, 100);
			if ($random_num <= 10) {
				self::collect_garbage();
			}
		}
	}
	/**
	 * Remove any old session files
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function collect_garbage() {
		Console::log("        Running session file garbage collection");
		$files = FindFiles::ls('/var/sessions', array('excludes' => array('.htaccess')), false);
		if (!empty($files)) {
			$expiry_time = time()-(60*60*24*3); // 3 days ago
			foreach ($files as $file) {
				if ($file->getMTime() <= $expiry_time) {
					$full_file_path = $file->getPathname();
					// If the file is 5 or more days old, delete it
					@unlink($full_file_path);
				}
			}
		}
	}
}
