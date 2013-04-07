<h2><?php echo count($ContactManager->contacts)?> Contacts Found</h2>
<?php

if (count($ContactManager->contacts) > 0) {
	foreach ($ContactManager->contacts as $contact) {
		/**
		 * All Available properties:
		 *
		 * $contact->id()
		 * $contact->designation() // ie. Mr, Mrs etc
		 * $contact->first_name()
		 * $contact->last_name()
		 * $contact->nick_name()
		 * $contact->full_name() // Concatenation of designation (if present), first name and last name
		 * $contact->email()
		 * $contact->company()
		 * $contact->title() // Career title
		 * $contact->phone()
		 * $contact->address1()
		 * $contact->address2()
		 * $contact->city()
		 * $contact->province()
		 * $contact->postal()
		 */
	?>
	<?php
		$link_start = '';
		$link_end = '';
		if ($contact->email() != null) {
			$link_start = '<a href="'.$ContactManager->url('send_email',$contact->id()).'">';
			$link_end = '</a>';
		}
	?>
			<?php echo $link_start.$contact->full_name().$link_end?>
	<?php
	}
}
else {
?>
	<p>No contacts found</p>
<?php
}
?>
