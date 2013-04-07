<?php
/**
 * Class for handling the base functionality of language setting and translation. Can either be extended or implemented by a module to provide
 * full translation functionality
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.2 $Id: i18n.php 14752 2012-12-01 21:28:48Z teknocat $
 */
class I18n extends EventObserver implements Singleton {
	/**
	 * Reference to instance of self
	 *
	 * @author Peter Epp
	 */
	private static $_instance;
	/**
	 * Current site locale - reference to the locale model. Defaults to Canadian English
	 *
	 * @var Locale
	 */
	private $_curr_locale;
	/**
	 * Default locale
	 *
	 * @var Locale
	 */
	private $_default_locale;
	/**
	 * Cache of all string translations by locale and msgid
	 *
	 * @var array
	 */
	private $_all_translations;
	/**
	 * Place to store a reference to the StringTranslation factory
	 *
	 * @var StringTranslation
	 */
	private $StringTranslation;
	/**
	 * Reference to the Locale model factory
	 *
	 * @var ModelFactory
	 */
	private $Locale;
	/**
	 * Prevent public instantiation
	 *
	 * @author Peter Epp
	 */
	private function __construct() {
		if (!Session::get('installer_running')) {
			$this->Locale = ModelFactory::instance('BiscuitLocale');
			$this->_default_locale = $this->Locale->find_default();
		}
		Event::add_observer($this);
	}
	/**
	 * Return a single instance of this object
	 *
	 * @return self
	 * @author Peter Epp
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Set the current locale
	 *
	 * @param string $locale 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_locale($locale = null) {
		$locale_set_success = true;
		$invalid_locale = '';
		if (empty($locale)) {
			$query_locale = Request::query_string('lang');
			if (empty($query_locale)) {
				$query_locale = $this->get_locale_from_uri();
			}
		}
		if (!empty($locale)) {
			$use_locale = $locale;
		} else if (!empty($query_locale)) {
			$use_locale = $query_locale;
		} else if (!empty($_COOKIE['BiscuitLocale'])) {
			$use_locale = $_COOKIE['BiscuitLocale'];
		}
		if (!empty($use_locale) && $use_locale != $this->_default_locale->code()) {
			$this->_curr_locale = $this->Locale->find_by_code($use_locale);
			if (!$this->_curr_locale) {
				$locale_set_success = false;
				$invalid_locale = $use_locale;
				$this->_curr_locale = $this->_default_locale;
			}
		} else {
			$this->_curr_locale = $this->_default_locale;
		}
		// Set PHP locale:
		$result = setlocale(LC_ALL,$this->_curr_locale->code());
		if (!$result) {
			// Failed to set locale, so locale provided was probably invalid. Ensure that it's deferred to default
			$result = setlocale(LC_ALL,$this->_default_locale->code());
			if (!$result) {
				// If default locale doesn't work for PHP, go with Canadian English as a safe fallback
				setlocale(LC_ALL,'en_CA');
				$php_default_locale = 'en_CA';
			} else {
				$php_default_locale = $this->_default_locale->code();
			}
			Console::log("NOTICE: the locale '".$this->_curr_locale->code()."' is not supported by PHP. As such, PHP functions that use the locale to automatically translate content will defer to the default locale of '".$php_default_locale."'");
			putenv("LANG=".$php_default_locale);
		} else {
			putenv("LANG=".$this->_curr_locale->code());
		}
		// Always remember current language for the next 30 days:
		Response::set_cookie('BiscuitLocale',$this->_curr_locale->code(),time()+(60*60*24*30),'/');
		Console::log("        Locale set to: ".$this->_curr_locale->code());
		if (!$locale_set_success && !Request::is_ajax() && ($this->uri_contains_locale() || !empty($query_locale))) {
			Console::log("Specified locale could not be set and locale is present in URL, redirecting to URL without locale...");
			Session::flash('user_error',sprintf("Unable to set locale. %s is not supported by this site.",$invalid_locale));
			if ($this->uri_contains_locale()) {
				$new_uri = $this->request_uri_without_locale();
			} else {
				$new_uri = Crumbs::strip_query_var_from_uri(Request::uri(),'lang');
			}
			Response::redirect($new_uri);
		}
		if (!Request::is_ajax()) {
			if ($this->_curr_locale->code() != $this->_default_locale->code() && !$this->uri_contains_locale()) {
				Console::log("Current locale is not the default and is not specified in the URL, redirecting to URL with locale...");
				// If the current locale is not the default and the URI does not contain locale, redirect to the current URI with locale in it:
				$new_uri = $this->_curr_locale->url();
				$new_uri = Crumbs::strip_query_var_from_uri($new_uri,'lang');
				Response::redirect($new_uri);
			} else if ($this->_curr_locale->code() == $this->_default_locale->code() && $this->uri_contains_locale()) {
				Console::log("URL contains locale and the current locale is same as the default, redirecting to current URL without locale...");
				Response::redirect($this->request_uri_without_locale());
			}
		}
	}
	/**
	 * Whether or not the request URI contains a locale
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function uri_contains_locale() {
		return preg_match('/^\/(([a-z]{2,3})_([A-Z]{2,3}))\/?.*$/',Request::uri());
	}
	/**
	 * Return the Request URI with locale stripped out of it
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function request_uri_without_locale() {
		$locale_from_uri = $this->get_locale_from_uri();
		if (!empty($locale_from_uri)) {
			$locale_from_uri = '/'.$locale_from_uri;
			$request_uri = Request::uri();
			return substr($request_uri,strlen($locale_from_uri));
		}
		return Request::uri();
	}
	/**
	 * Extract the locale from URI, if present, and return it
	 *
	 * @return string|null
	 * @author Peter Epp
	 */
	public function get_locale_from_uri() {
		preg_match('/^\/(([a-z]{2,3})_([A-Z]{2,3}))\/?.*$/',Request::uri(),$matches);
		if (!empty($matches) && !empty($matches[1])) {
			return $matches[1];
		}
		return null;
	}
	/**
	 * Load string translations into memory for this request. Use cache file if present, otherwise read from database and then cache lazily
	 * if config directory is writable
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function load_translations() {
		if (Session::get('installer_running')) {
			return;
		}
		$translation_cache_file = SITE_ROOT.'/var/cache/string_translations.cache';
		if (file_exists($translation_cache_file)) {
			$this->_all_translations = unserialize(file_get_contents($translation_cache_file));
		} else {
			$all_translations = $this->StringTranslation()->find_all();
			if (!empty($all_translations)) {
				foreach ($all_translations as $translation) {
					$this->_all_translations[$translation->locale()][$translation->msgid()] = $translation->msgstr();
				}
			}
			if (is_writable(SITE_ROOT.'/config')) {
				file_put_contents($translation_cache_file,serialize($this->_all_translations));
			}
		}
	}
	/**
	 * Call the appropriate gettext function to translate a string
	 *
	 * @param string $text 
	 * @param string $domain 
	 * @return void
	 * @author Peter Epp
	 */
	public function translate($text) {
		if (Session::get('installer_running')) {
			return $text;
		}
		if (!empty($this->_all_translations[$this->_curr_locale->code()]) && !empty($this->_all_translations[$this->_curr_locale->code()][$text])) {
			return $this->_all_translations[$this->_curr_locale->code()][$text];
		} else {
			if ($this->_curr_locale->code() != $this->_default_locale->code()) {
				// Stub the translation placeholder into the database, but only if current locale is not en_CA, which is the default
				$this->_stub_in_new_translation($text);
			}
			return $text;
		}
	}
	/**
	 * Stub a new string into the translation table
	 *
	 * @param string $text 
	 * @return void
	 * @author Peter Epp
	 */
	private function _stub_in_new_translation($text) {
		if (!empty($text) && $text != " " && $text != "&nbsp;" && (empty($this->_all_translations[$this->_curr_locale->code()]) || !array_key_exists($text,$this->_all_translations[$this->_curr_locale->code()]))) {
			$data = array(
				'locale' => $this->_curr_locale->code(),
				'msgid'  => $text
			);
			$translation = $this->StringTranslation()->create($data);
			$translation->save();
			$this->_all_translations[$translation->locale()][$translation->msgid()] = $translation->msgstr();
			$translation_cache_file = SITE_ROOT.'/var/cache/string_translations.cache';
			if (file_exists($translation_cache_file)) {
				@unlink($translation_cache_file);
			}
		}
	}
	/**
	 * Instantiate a StringTranslation model if not already done and return it
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function StringTranslation() {
		if (empty($this->StringTranslation) && !is_object($this->StringTranslation)) {
			$this->StringTranslation = ModelFactory::instance('StringTranslation');
		}
		return $this->StringTranslation;
	}
	/**
	 * Return the full current locale code
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function locale() {
		return $this->_curr_locale->code();
	}
	/**
	 * Return the locale in format for meta data - with hyphen instead of underscore
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function meta_locale() {
		$curr_locale = $this->_curr_locale->code();
		$curr_locale = str_replace("_","-",$curr_locale);
		return $curr_locale;
	}
	/**
	 * Return the language portion of the locale for use in the HTML lang attribute
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function html_lang() {
		return $this->_curr_locale->iso_639_lang_code();
	}
	/**
	 * Upon request dispatch, catch any requests for properties file and fetch, compile and render properties file content
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_dispatch_request() {
		if (Request::user_input('js_translations_request')) {
			$js_properties_output = '# No translations here';
			$request_uri = Crumbs::strip_query_var_from_uri(Request::uri(),'_');
			Console::log("Responding to JS translations file request: ".$request_uri);
			if ($request_uri == '/var/cache/js/Messages_'.$this->_curr_locale->code().'.properties') {
				Biscuit::instance()->set_never_cache(); // We don't want this stored in the page cache
				Console::log("Generate JS translations for ".$this->_curr_locale->code());
				// Start with core messages:
				$js_properties_output = Crumbs::capture_include('framework/js/Messages.properties.php');

				// Add theme properties file, if present:
				$theme_properties_file = Biscuit::instance()->Theme->theme_dir().'/js/Messages.properties.php';
				if (Crumbs::file_exists_in_load_path($theme_properties_file)) {
					$js_properties_output .= "\n\n".Crumbs::capture_include($theme_properties_file);
				}

				// Now round up properties files provided by modules or extensions:
				$module_factory = ModelFactory::instance('Module');
				$modules = $module_factory->find_all_by('installed',1);
				if (!empty($modules)) {
					foreach ($modules as $module) {
						$customized_properties_file = 'modules/customized/'.AkInflector::underscore($module->name()).'/js/Messages.properties.php';
						$properties_file = 'modules/'.AkInflector::underscore($module->name()).'/js/Messages.properties.php';
						if (Crumbs::file_exists_in_load_path($customized_properties_file)) {
							$js_properties_output .= "\n\n# ".ucwords(AkInflector::humanize(AkInflector::underscore($module->name())))." module translations:\n".Crumbs::capture_include($customized_properties_file);
						} else if (Crumbs::file_exists_in_load_path($properties_file)) {
							$js_properties_output .= "\n\n# ".ucwords(AkInflector::humanize(AkInflector::underscore($module->name())))." module translations:\n".Crumbs::capture_include($properties_file);
						}
					}
				}
				$extension_factory = ModelFactory::instance('Extension');
				$extensions = $extension_factory->find_all();
				if (!empty($extensions)) {
					foreach ($extensions as $extension) {
						$customized_properties_file = 'extensions/customized/'.AkInflector::underscore($extension->name()).'/js/Messages.properties.php';
						$properties_file = 'extensions/'.AkInflector::underscore($extension->name()).'/js/Messages.properties.php';
						if (Crumbs::file_exists_in_load_path($customized_properties_file)) {
							$js_properties_output .= "\n\n# ".ucwords(AkInflector::humanize(AkInflector::underscore($extension->name())))." extension translations:\n".Crumbs::capture_include($customized_properties_file);
						} else if (Crumbs::file_exists_in_load_path($properties_file)) {
							$js_properties_output .= "\n\n# ".ucwords(AkInflector::humanize(AkInflector::underscore($extension->name())))." extension translations:\n".Crumbs::capture_include($properties_file);
						} 
					}
				}
				if (!empty($this->_js_translations)) {
					$js_properties_output .= "\n\n".implode("\n\n", $this->_js_translations);
				}
			}
			if (Crumbs::ensure_directory(SITE_ROOT.'/var/cache/js')) {
				$js_properties_cache_file = SITE_ROOT.$request_uri;
				file_put_contents($js_properties_cache_file, $js_properties_output);
			}
			Response::content_type('text/plain; charset=utf8');
			Biscuit::instance()->render_with_template(false);
			Biscuit::instance()->render($js_properties_output);
			Bootstrap::end_program(true);
		}
	}
	/**
	 * Empty page cache on request
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_empty_cache_request() {
		$js_properties_cache_file = SITE_ROOT.'/var/cache/js/Messages_'.$this->_curr_locale->code().'.properties';
		if (file_exists($js_properties_cache_file)) {
			@unlink($js_properties_cache_file);
		}
	}
}

/**
 * Shortcut to i18n translate function
 *
 * @param string $text 
 * @param string $domain 
 * @return void
 * @author Peter Epp
 */
function __($text) {
	return I18n::instance()->translate($text);
}
