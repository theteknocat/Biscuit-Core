<?php
/**
 * Model the locales table
 *
 * @package Core
 * @author Peter Epp
 */
class BiscuitLocale extends AbstractModel {
	/**
	 * Return the full ISO locale code by combining the 639 language code with the 3166 country code, ensuring correct case
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function code() {
		return strtolower($this->iso_639_lang_code()).'_'.strtoupper($this->iso_3166_country_code());
	}
	/**
	 * Return full site-root-relative path to an icon file for the locale, if present
	 *
	 * @return string|null
	 * @author Peter Epp
	 */
	public function icon_path() {
		$icon_path_prefix = 'images/locales/icon-'.$this->code();
		$icon_paths = array(
			$icon_path_prefix.'.jpg',
			$icon_path_prefix.'.gif',
			$icon_path_prefix.'.png'
		);
		foreach ($icon_paths as $icon_path) {
			if ($full_icon_path = Crumbs::file_exists_in_load_path($icon_path,true)) {
				return $full_icon_path;
				break;
			}
		}
		return null;
	}
	/**
	 * Return the current request URI with current locale setting in it
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function url($url = null) {
		if (empty($url)) {
			$url = Request::uri();
		}
		$url = trim($url,'/');
		if (empty($url)) {
			return '/'.$this->code().'/';
		}
		return '/'.$this->code().'/'.$url.'/';
	}
	/**
	 * Whether or not this locale is the active one
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function is_active() {
		return ($this->code() == I18n::instance()->locale());
	}
	/**
	 * Return the actual table name as it's not the same as the class name
	 *
	 * @return string
	 * @author Peter Epp
	 **/
	public static function db_tablename() {
		return 'locales';
	}
}
