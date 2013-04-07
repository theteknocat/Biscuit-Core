<?php
class LocaleFactory extends ModelFactory {
	/**
	 * Find the default locale
	 *
	 * @return Locale|null
	 * @author Peter Epp
	 */
	public function find_default() {
		return $this->model_from_query("SELECT * FROM `locales` WHERE `is_default` = 1 LIMIT 1");
	}
	/**
	 * Find a locale by complete ISO locale code (639 language code plus 3166 country code)
	 *
	 * @param string $iso_locale_code 
	 * @return Locale|null
	 * @author Peter Epp
	 */
	public function find_by_code($iso_locale_code) {
		$iso_code_parts = explode("_",$iso_locale_code);
		if (count($iso_code_parts) == 2) {
			return $this->model_from_query("SELECT * FROM `locales` WHERE `iso_639_lang_code` = ? AND `iso_3166_country_code` = ?",$iso_code_parts);
		}
		return null;
	}
}
