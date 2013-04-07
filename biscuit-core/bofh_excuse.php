<?php
/**
 * BOFH Excuse generator class. This is just here for shits and giggles. Use it if you want to give your users technical excuses when your application breaks.
 * Words taken from {@link http://digitalfreaks.org/~lavalamp/BOFH_ExcuseBoard.html}
 *
 * @package Core
 * @author Peter Epp
 */
class BofhExcuse {
	/**
	 * First set of words
	 *
	 * @var array
	 */
	private static $_words1 = array(
		"Temporary",
		"Intermittant",
		"Partial",
		"Redundant",
		"Total",
		"Multiplexed",
		"Inherent",
		"Duplicated",
		"Dual-Homed",
		"Synchronous",
		"Bidirectional",
		"Serial",
		"Asynchronous",
		"Multiple",
		"Replicated",
		"Non-Replicated",
		"Unregistered",
		"Non-Specific",
		"Generic",
		"Migrated",
		"Localised",
		"Resignalled",
		"Dereferenced",
		"Nullified",
		"Aborted",
		"Serious",
		"Minor",
		"Major",
		"Extraneous",
		"Illegal",
		"Insufficient",
		"Viral",
		"Unsupported",
		"Outmoded",
		"Legacy",
		"Permanent",
		"Invalid",
		"Deprecated",
		"Virtual",
		"Unreportable",
		"Undetermined",
		"Undiagnosable",
		"Unfiltered",
		"Static",
		"Dynamic",
		"Delayed",
		"Immediate",
		"Nonfatal",
		"Fatal",
		"Non-Valid",
		"Unvalidated",
		"Non-Static",
		"Unreplicatable",
		"Non-Serious"
	);
	/**
	 * Second set of words
	 *
	 * @var array
	 */
	private static $_words2 = array(
		"Array",
		"Systems",
		"Hardware",
		"Software",
		"Firmware",
		"Backplane",
		"Logic-Subsystem",
		"Integrity",
		"Subsystem",
		"Memory",
		"Comms",
		"Integrity",
		"Checksum",
		"Protocol",
		"Parity",
		"Bus",
		"Timing",
		"Synchronisation",
		"Topology",
		"Transmission",
		"Reception",
		"Stack",
		"Framing",
		"Code",
		"Programming",
		"Peripheral",
		"Environmental",
		"Loading",
		"Operation",
		"Parameter",
		"Syntax",
		"Initialisation",
		"Execution",
		"Resource",
		"Encryption",
		"Decryption",
		"File",
		"Precondition",
		"Authentication",
		"Paging",
		"Swapfile",
		"Service",
		"Gateway",
		"Request",
		"Proxy",
		"Media",
		"Registry",
		"Configuration",
		"Metadata",
		"Streaming",
		"Retrieval",
		"Installation",
		"Library",
		"Handler"
	);
	/**
	 * Third set of words
	 *
	 * @var array
	 */
	private static $_words3 = array(
		"Interruption",
		"Destabilisation",
		"Destruction",
		"Desynchronisation",
		"Failure",
		"Dereferencing",
		"Overflow",
		"Underflow",
		"NMI",
		"Interrupt",
		"Corruption",
		"Anomoly",
		"Seizure",
		"Override",
		"Reclock",
		"Rejection",
		"Invalidation",
		"Halt",
		"Exhaustion",
		"Infection",
		"Incompatibility",
		"Timeout",
		"Expiry",
		"Unavailability",
		"Bug",
		"Condition",
		"Crash",
		"Dump",
		"Crashdump",
		"Stackdump",
		"Problem",
		"Lockout"
	);
	/**
	 * Fourth set of words
	 *
	 * @var array
	 */
	private static $_words4 = array("Error", "Problem", "Warning", "Signal", "Flag");
	/**
	 * Generate a random excuse
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public static function generate() {
		$first_word_key = mt_rand(0, (count(self::$_words1)-1));
		$second_word_key = mt_rand(0, (count(self::$_words2)-1));
		$third_word_key = mt_rand(0, (count(self::$_words3)-1));
		$excuse = self::$_words1[$first_word_key].' '.self::$_words2[$second_word_key].' '.self::$_words3[$third_word_key];
		if (mt_rand(1, 3) == 2) {
			$fourth_word_key = mt_rand(0, (count(self::$_words4)-1));
			$excuse .= ' '.self::$_words4[$fourth_word_key];
		}
		return $excuse;
	}
}
