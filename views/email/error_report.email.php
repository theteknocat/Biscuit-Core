This message is to inform you of a Biscuit Application error.

URL: <?php echo $full_url; ?>

Date/time: <?php echo $error_date; ?>

Active user: <?php echo $username; ?>

<?php if (!empty($post_data)) {
	?>Submitted Form Data:
<?php print_r($post_data);
} ?>

Error Message:
<?php echo $error_message; ?>


File: <?php echo $error_file; ?>

Line: <?php echo $error_line; ?>

Backtrace:
<?php echo $backtrace; ?>

==============================================================
Sent from <?php echo STANDARD_URL ?>

==============================================================
