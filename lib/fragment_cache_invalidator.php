<?php
/**
 * Event observer to handle invalidation of fragment caches when models are saved or deleted
 *
 * @package Core
 * @author Peter Epp
 */
class FragmentCacheInvalidator extends EventObserver {
	/**
	 * Add this object to list of event observers
	 *
	 * @author Peter Epp
	 */
	public function __construct() {
		Event::add_observer($this);
	}
	/**
	 * Invalidate cache on model save
	 *
	 * @param object $model 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_successful_save($model) {
		$this->invalidate_cache($model);
	}
	/**
	 * Invalidate cache on model deletion
	 *
	 * @param string $model 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_successful_delete($model) {
		$this->invalidate_cache($model);
	}
	/**
	 * Perform the cache invalidation for the affected model
	 *
	 * @param object $model 
	 * @return void
	 * @author Peter Epp
	 */
	private function invalidate_cache($model) {
		$fcache = new FragmentCache(Crumbs::normalized_model_name($model),$model->id());
		$fcache->invalidate_all();
	}
	/**
	 * Empty the entire fragment cache folder
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function empty_entire_cache() {
		Console::log("Empty fragment cache");
		$files = FindFiles::ls('/fragment-cache',array('types' => 'cache'),true,true);
		if (!empty($files)) {
			foreach ($files as $file) {
				@unlink($file);
			}
			$dirs = FindFiles::ls('/fragment-cache',array('include_files' => false, 'include_directories' => true),true,true);
			if (!empty($dirs)) {
				rsort($dirs);
				foreach ($dirs as $dir) {
					@rmdir($dir);
				}
			}
		}
	}
	/**
	 * Empty entire fragment cache folder
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_empty_cache_request() {
		$this->empty_entire_cache();
	}
}
