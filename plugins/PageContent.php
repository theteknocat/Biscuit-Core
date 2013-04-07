<?php
require_once('plugins/PageIndex.php');
/**
 * Model for the PageContent plugin which allows arbitrary editing of static page content
 *
 * @package Plugins
 * @author Peter Epp
 */
class PageContent extends AbstractModel {
	/**
	 * Page title
	 *
	 * @var string
	 */
	var $title;
	/**
	 * Parent id of the page
	 *
	 * @var string
	 */
	var $parent;
	/**
	 * Page slug
	 *
	 * @var string
	 */
	var $shortname;
	/**
	 * META tag description
	 *
	 * @var string
	 */
	var $description = "";
	/**
	 * META tag keywords
	 *
	 * @var string
	 */
	var $keywords = "";
	/**
	 * HTML content of the page
	 *
	 * @var string
	 */
	var $content;
	/**
	 * Date of update as YYYY-MM-DD HH:MM:SS
	 *
	 * @var string
	 */
	
	var $updated;
	/**
	 * Find one item in the database
	 *
	 * @param mixed $id 
	 * @return object Instance of this model
	 * @author Your Name
	 */
	function find($id) {
		$id = (int)$id;
		$query = "SELECT pi.id, pi.parent, pi.shortname, pi.title, pi.description, pi.keywords, pc.content, pc.updated FROM page_index pi LEFT JOIN page_content pc ON (pc.id = pi.id) WHERE pi.id = ".$id;
		$content = PageContent::content_from_query($query);
		if (!$content->content()) {
			$default_content = '<p>There is presently no content available for this page. Please check back soon.</p>';
			$updated = date("Y-m-d H:i:s");
			DB::query("INSERT INTO page_content SET id = {$id}, content = '{$default_content}', updated = '{$updated}'");
			Console::log("                        No content found for page with id ".$id.". Creating new database entry with default content.");
			$content->set_attribute('content',$default_content);
			$content->set_attribute('updated',$updated);
		}
		return $content;
	}
	function title()         {		return $this->get_attribute('title');		}
	function parent()        {		return $this->get_attribute('parent');		}
	function shortname()     {		return $this->get_attribute('shortname');	}
	function description()   {		return $this->get_attribute('description');	}
	function keywords()      {		return $this->get_attribute('keywords');	}
	function content()       {		return $this->get_attribute('content');		}
	function updated()       {		return $this->get_attribute('updated');		}
	/**
	 * Validate user input
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function validate() {
		if (!$this->title()) {
			$this->set_error("Please enter a page title");
		}
		else {
			$this->set_attribute('title',H::purify_text($this->title()));
		}
		if (!$this->content()) {
			// Set some default content to display if no real text content was entered:
			$this->set_attribute('content','<p>There is presently no content available for this page. Please check back soon.</p>');
		}
		$this->set_attribute('description',H::purify_text($this->description()));
		$this->set_attribute('keywords',H::purify_text($this->keywords()));
		$this->set_attribute('updated',date('Y-m-d H:i:s'));
		$this->has_been_validated(true);
		return (!$this->errors());
	}
	function save_filter($data) {
		unset($data['title'],$data['parent'],$data['shortname'],$data['description'],$data['keywords']);
		return $data;
	}
	/**
	 * Save data to the object's defined database table
	 *
	 * @param array $data Associative array of data with keys matching the database columns to store
	 * @return void
	 * @author Peter Epp
	 */
	function save() {
		// Save the page content:
		$result = parent::save();
		if ($result) {
			Console::log("Saved page content for ".$this->title().", now saving page index...");
			// Save the page index data:
			$data = $this->get_attributes();
			$page_index = PageIndex::find($data['id']);
			$page_index->set_attributes($data);
			return $page_index->save();
		}
		return false;
	}
	/**
	 * Build an object from a database query
	 *
	 * @param string $query Database query
	 * @return object
	 * @author Peter Epp
	 */
	function content_from_query($query) {
		return parent::model_from_query("PageContent",$query);
	}
	function db_tablename() {
		return 'page_content';
	}
	function db_create_query() {
		return 'CREATE TABLE  `page_content` (
		`id` INT( 9 ) NOT NULL PRIMARY KEY ,
		`content` LONGTEXT NOT NULL,
		`updated` DATETIME NOT NULL
		) TYPE = MyISAM';
	}
}
?>