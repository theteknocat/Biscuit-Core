<?php
/**
 * Event observer to handle invalidation of fragment caches when models are saved or deleted
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0 $Id: fragment_cache_invalidator.php 14778 2013-01-15 05:32:34Z teknocat $
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
	public function invalidate_cache($model) {
		$fcache = new FragmentCache(Crumbs::normalized_model_name($model),$model->id());
		$fcache->invalidate_all();
	}
	/**
	 * Empty entire fragment cache on cache empty requests
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_empty_cache_request() {
		FragmentCache::empty_all();
	}
}
