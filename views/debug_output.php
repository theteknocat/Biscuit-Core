<pre>
Debug Information

Request: {
	URI:               <?php echo Request::uri()?>

	Status:            <?php echo $this->http_status?>

	Method:            <?php echo Request::method()?>

	Type:              <?php echo Request::type()?>

	Page Name:         <?php echo $this->page_name?>

	Query Params:      <?php echo ((Request::query_string() !== null) ? "\n".htmlentities(print_r(Request::query_string(),true)) : "None") ?>

	Has Post Data:     <?php echo ((Request::is_post() && !empty($this->raw_user_input)) ? "Yes, content:\n".htmlentities(print_r($this->raw_user_input,true)) : "No")?>

}
Session: {
	<?php echo htmlentities(print_r(Session::contents(),true))?>
}
</pre>