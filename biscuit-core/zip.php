<?php
/**
 * Generate zip from a list of files on the fly. Thanks to Gallery 3 for most of this code.
 *
 * NOTE: This merely STORES files in a ZIP file container, it doesn't perform any actual compression. If you need to compress to zip format, install and use the
 * PHP Zip extension.
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0 $Id: zip.php 14770 2013-01-04 19:05:56Z teknocat $
 */
class Zip {
	/**
	 * Setting for flat directory structure
	 */
	const FLAT_DIR_STRUCTURE = true;
	/**
	 * Setting for full directory structure
	 */
	const FULL_DIR_STRUCTURE = false;
	/**
	 * List of files to include in the zip file
	 *
	 * @var array
	 */
	private $_files = array();
	/**
	 * Output filename
	 *
	 * @var string
	 */
	private $_output_file = '';
	/**
	 * Array of just the filenames keyed to the full file paths
	 *
	 * @var array
	 */
	private $_short_filenames = array();
	/**
	 * Set data
	 *
	 * @param string $this->_files 
	 * @param string $output_file 
	 * @author Peter Epp
	 */
	public function __construct($files, $output_file, $flatten_structure = true, $base_dir = SITE_ROOT) {
		$this->_files = $files;
		$this->_output_file = $output_file;
		foreach($files as $f) {
			if ($flatten_structure) {
				$fname_bits = explode('/',$f);
				$filename = end($fname_bits);
				$this->_short_filenames[$f] = $filename;
			} else {
				$this->_short_filenames[$f] = substr($f,strlen($base_dir));
			}
		}
	}
	/**
	 * Generate and save the zip file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function generate() {
		// http://www.pkware.com/documents/casestudies/APPNOTE.TXT (v6.3.2)
		$lfh_offset = 0;
		$cds = '';
		$cds_offset = 0;
		foreach($this->_files as $f) {
			$filename = $this->_short_filenames[$f];
			$f_namelen = strlen($filename);
			$f_size = filesize($f);
			$f_mtime = $this->unix2dostime(filemtime($f));
			$f_crc32 = $this->fixbug45028(hexdec(hash_file('crc32b', $f, false)));

			// Local file header
			$file_header = pack('VvvvVVVVvva' . $f_namelen,
				0x04034b50,         // local file header signature (4 bytes)
				0x0a,               // version needed to extract (2 bytes) => 1.0
				0x0800,             // general purpose bit flag (2 bytes) => UTF-8
				0x00,               // compression method (2 bytes) => store
				$f_mtime,           // last mod file time and date (4 bytes)
				$f_crc32,           // crc-32 (4 bytes)
				$f_size,            // compressed size (4 bytes)
				$f_size,            // uncompressed size (4 bytes)
				$f_namelen,         // file name length (2 bytes)
				0,                  // extra field length (2 bytes)

				$filename           // file name (variable size)
				// extra field (variable size) => n/a
			);
			
			$result = file_put_contents($this->_output_file, $file_header, FILE_APPEND);
			if (!$result) {
				return false;
			}

			// File data
			$result = file_put_contents($this->_output_file, file_get_contents($f), FILE_APPEND);
			if (!$result) {
				return false;
			}

			// Data descriptor (n/a)

			// Central directory structure: File header
			$cds .= pack('VvvvvVVVVvvvvvVVa' . $f_namelen,
				0x02014b50,         // central file header signature (4 bytes)
				0x031e,             // version made by (2 bytes) => v3 / Unix
				0x0a,               // version needed to extract (2 bytes) => 1.0
				0x0800,             // general purpose bit flag (2 bytes) => UTF-8
				0x00,               // compression method (2 bytes) => store
				$f_mtime,           // last mod file time and date (4 bytes)
				$f_crc32,           // crc-32 (4 bytes)
				$f_size,            // compressed size (4 bytes)
				$f_size,            // uncompressed size (4 bytes)
				$f_namelen,         // file name length (2 bytes)
				0,                  // extra field length (2 bytes)
				0,                  // file comment length (2 bytes)
				0,                  // disk number start (2 bytes)
				0,                  // internal file attributes (2 bytes)
				0x81b40000,         // external file attributes (4 bytes) => chmod 664
				$lfh_offset,        // relative offset of local header (4 bytes)

				$filename           // file name (variable size)
				// extra field (variable size) => n/a
				// file comment (variable size) => n/a
			);

			// Update local file header/central directory structure offset
			$cds_offset = $lfh_offset += 30 + $f_namelen + $f_size;
		}

		// Archive decryption header (n/a)
		// Archive extra data record (n/a)

		// Central directory structure: Digital signature (n/a)
		$result = file_put_contents($this->_output_file, $cds, FILE_APPEND);
		if (!$result) {
			return false;
		}

		// Zip64 end of central directory record (n/a)
		// Zip64 end of central directory locator (n/a)

		// End of central directory record
		$numfile = count($this->_files);
		$cds_len = strlen($cds);
		$end_of_file_record = pack('VvvvvVVv',
			0x06054b50,             // end of central dir signature (4 bytes)
			0,                      // number of this disk (2 bytes)
			0,                      // number of the disk with the start of
			// the central directory (2 bytes)
			$numfile,               // total number of entries in the
			// central directory on this disk (2 bytes)
			$numfile,               // total number of entries in the
			// central directory (2 bytes)
			$cds_len,               // size of the central directory (4 bytes)
			$cds_offset,            // offset of start of central directory
			// with respect to the
			// starting disk number (4 bytes)
			0                       // .ZIP file comment length (2 bytes)
			// .ZIP file comment (variable size)
		);
		return file_put_contents($this->_output_file, $end_of_file_record, FILE_APPEND);
	}
	/**
	 * Return the full path to the output file
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function zip_file() {
		return $this->_output_file;
	}
	/**
	 * returns DOS date and time of the timestamp
	 *
	 * @return integer             DOS date and time
	 * @param  integer _timestamp  Unix timestamp
	 */
	private function unix2dostime($timestamp) {
		$timebit = getdate($timestamp);

		if ($timebit['year'] < 1980) {
			return (1 << 21 | 1 << 16);
		}

		$timebit['year'] -= 1980;

		return ($timebit['year']    << 25 | $timebit['mon']     << 21 |
			$timebit['mday']    << 16 | $timebit['hours']   << 11 |
			$timebit['minutes'] << 5  | $timebit['seconds'] >> 1);
	}
	/**
	 * Work around bug 45028 in PHP less than 5.2.7
	 *
	 * @see {@link http://bugs.php.net/bug.php?id=45028}
	 */
	private function fixbug45028($hash) {
		return (version_compare(PHP_VERSION, '5.2.7', '<'))
			? (($hash & 0x000000ff) << 24) + (($hash & 0x0000ff00) << 8)
			+ (($hash & 0x00ff0000) >> 8) + (($hash & 0xff000000) >> 24)
			: $hash;
	}
}
