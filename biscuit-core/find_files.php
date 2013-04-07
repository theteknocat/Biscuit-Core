<?php
/**
 * The name says it all
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: find_files.php 14196 2011-09-01 19:08:39Z teknocat $
 */
class FindFiles extends FilterIterator {
	/**
	 * Whether or not to include directories in the results
	 *
	 * @var bool
	 */
	private $include_directories = false;
	/**
	 * Whether or not to include files in the results
	 *
	 * @var string
	 */
	private $include_files = true;
	/**
	 * Whether or not to follow symbolic links
	 *
	 * @var string
	 */
	private $follow_symlinks = true;
	/**
	 * Regular expression to filter filenames by
	 *
	 * @var regex
	 */
	private $regex = null;
	/**
	 * List of file extensions to match
	 *
	 * @var array
	 */
	private $extensions = array();
	/**
	 * Filenames to exclude from results. Defaults to .svn folders, Dreamweaver _notes folders, and Mac OS .DS_Store files
	 *
	 * @var array
	 */
	private $exclude_files = array('.svn','_notes','.DS_Store');
	/**
	 * Whether or not the file listing is recursive
	 *
	 * @var bool
	 */
	private $is_recursive = false;
	/**
	 * Construct the directory iterator for the given path
	 *
	 * @param string $path 
	 * @param string $regex Regular expression to search for
	 * @author Peter Epp
	 */
	public function __construct($path, $filter_options = array()) {
		if (empty($filter_options['excludes'])) {
			$filter_options['excludes'] = array();
		}
		$default_excludes = $this->exclude_files;
		$this->exclude_files = array_merge((array)$filter_options['excludes'],$default_excludes);
		if (!empty($filter_options['types'])) {
			$this->extensions = (array)$filter_options['types'];
		}
		if (!empty($filter_options['match_pattern'])) {
			$this->regex = "/".$filter_options['match_pattern']."/i";	// Make it case-insensitive for increased tolerance
		}
		if (isset($filter_options['include_directories']) and is_bool($filter_options['include_directories'])) {
			$this->include_directories = $filter_options['include_directories'];
		}
		if (isset($filter_options['include_files']) and is_bool($filter_options['include_files'])) {
			$this->include_files = $filter_options['include_files'];
		}
		if (isset($filter_options['follow_symlinks']) and is_bool($filter_options['follow_symlinks'])) {
			$this->follow_symlinks = $filter_options['follow_symlinks'];
		}
		if ($filter_options['recursive']) {
			$this->is_recursive = true;
			$it = new RecursiveDirectoryIterator($path);
			$directory_iterator = new RecursiveIteratorIterator($it,RecursiveIteratorIterator::CHILD_FIRST);
		} else {
			$directory_iterator = new DirectoryIterator($path);
		}
		parent::__construct($directory_iterator);
	}
	/**
	 * Whether or not the file should be included in the results based on various filters
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function accept() {
		$current_file = $this->current();
		if (method_exists($current_file,'isDot') && $current_file->isDot()) {
			return false;
		}
		if (in_array($current_file->getFilename(), $this->exclude_files)) {
			return false;
		} else if ($this->is_recursive) {
			foreach ($this->exclude_files as $exclude_file) {
				$file_path = $current_file->getPath();
				$path_bits = array_filter(explode('/',$file_path));
				if (in_array($exclude_file,$path_bits)) {
					return false;
				}
			}
		}
		if (!$this->follow_symlinks and $current_file->isLink()) {
			return false;
		}
		if (!$this->include_directories and $current_file->isDir()) {
			return false;
		}
		if (!$this->include_files and $current_file->isFile()) {
			return false;
		}
		$path_info = pathinfo($current_file);
		if (!empty($this->extensions) and !in_array($path_info['extension'], $this->extensions)) {
			return false;
		}
		if (!empty($this->regex) and !preg_match($this->regex,$current_file)) {
			return false;
		}
		return true;
	}
	/**
	 * Get a file listing for a given directory. Returns as string array by default with the option to request the 
	 *
	 * @param string $path 
	 * @param array $options This can include "types" as a single file extension or array of file extensions, "excludes" as a single filename to exclude or array of filenames, and "include_directories" as a boolean which is false by default.
	 * @return array|DirectoryIterator If $string_array is true it will always be an array of string filenames without the path.  Otherwise, in non-recursive listings an iterable object and in recursive listings it will be an array of SplFileInfo objects.
	 * @author Peter Epp
	 */
	public static function ls($path, $filter_options = array(), $string_array = true, $recursive = false) {
		$full_path = SITE_ROOT.$path;
		if (!file_exists($full_path)) {
			trigger_error("FindFiles::ls() path not found: ".$full_path, E_USER_NOTICE);
			return array();
		}
		$filter_options['recursive'] = $recursive;
		$files = new FindFiles($full_path, $filter_options);
		if ($string_array) {
			$string_filenames = array();
			foreach ($files as $entry) {
				$string_filenames[] = (string)$entry;
			}
			@natcasesort($string_filenames);
			return $string_filenames;
		} else {
			return $files;
		}
	}
	
}
?>