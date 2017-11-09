<?php
/**
* MEDLINE (PubMed .nbib) driver for RefLib
* See 
* - https://www.nlm.nih.gov/bsd/disted/pubmedtutorial/030_080.html
* - https://www.ncbi.nlm.nih.gov/books/NBK3827/table/pubmedhelp.T.medline_display/?report=objectonly
* - https://www.nlm.nih.gov/bsd/mms/medlineelements.html
*
* NOTE: Adapted from ris.php driver
*/
class RefLib_medline {
	var $driverName = 'MEDLINE';

	/**
	* The parent instance of the RefLib class
	* @var class
	*/
	var $parent;

	/**
	* Simple key/val mappings
	* Each key is the MEDLINE / nbib format name, each Val is the RefLib version
	* Place preferential keys for output at the top if multiple incoming keys match
	* @var array
	*/
	var $_mapHash = array(
		'PMID' => 'accession-num',
		'LA' => 'language',
		'GN' => 'notes',
		'IS' => 'isbn',
		'ISBN' => 'isbn',
		'TI' => 'title',
		'BTI' => 'title-secondary',
		'VI' => 'volume',
		'PG' => 'pages',
		'IP' => 'number', // Issue #
		'JT' => 'periodical-title',
		'TA' => 'alt-journal',
		'AB' => 'abstract',
		'PL' => 'address',
		'OWN'=> 'database-provider',
		'SO' => 'notes',
		'CI' => 'custom1',
		'PMC' => 'custom2',
		'MID'=> 'custom6,'
		
	);

	/**
	* Similar to $_mapHash but this time each value is an array
	* Place preferential keys for output at the top if multiple incoming keys match
	* @var array
	*/
	var $_mapHashArray = array(
		// Preferred keys
		'AU' => 'authors',
		'OT' => 'keywords',
		'MH' => 'keywords',
	);
	/**
	* Maps abbreviated months (3 chars) to month index.
	*/
	var $_mapMonths = array(
		'Jan' => '01',
		'Feb' => '02',
		'Mar' => '03',
		'Apr' => '04',
		'May' => '05',
		'Jun' => '06',
		'Jul' => '07',
		'Aug' => '08',
		'Sep' => '09',
		'Oct' => '10',
		'Nov' => '11',
		'Dec' => '12'
	);
	/**
	* Maps publication types to $refTypes
	* https://www.nlm.nih.gov/mesh/pubtypes.html
	* https://www.ncbi.nlm.nih.gov/books/NBK3827/table/pubmedhelp.T.publication_types/
	*/
	var $_mapPublicationTypes = array(
		'Book' => 'Book',
		'Book Chapter' => 'Book Section',
		'Case Reports' => 'Case',
		'Dataset' => 'Dataset',
		'Dictionary' => 'Dictionary',
		'Editorial' => 'Journal Article',
		'Government Publications' => 'Government Document',
		'Introductory Journal Article' => 'Journal Article',
		'Journal Article' => 'Journal Article',
		'Legal Cases' => 'Legal Rule or Regulation',
		'Legislation' => 'Legal Rule or Regulation',
		//'News' => 'Journal Article',
		'Newspaper Article' => 'Newspaper Article',
		'Personal Narratives' => 'Personal Communication',
		'Technical Report' => 'Report',
	);
	/**
	* Escape a string in an EndNote compatible way
	* @param string $string The string to be escaped
	* @return string The escaped string
	*/
	function Escape($string) {
		return strtr($string, array(
			"\r" => '\n',
		));
	}

	/**
	* Computes the default filename if given a $salt
	* @param string $salt The basic part of the filename to use
	* @return string The filename including extension to use as default
	*/
	function GetFilename($salt = 'medline') {
		return "$salt.nbib";
	}

	function GetContents() {
		$out = '';
		foreach ($this->parent->refs as $refraw) {
			$ref = $refraw;
			$out .= "\r\n";
			foreach ($this->_mapHashArray as $k => $v)
				if (isset($ref[$v])) {
					foreach ((array) $ref[$v] as $val)
						$out .= "$k  - " . $this->Escape($val) . "\n";
					unset($ref[$v]); // Remove it from the reference copy so we dont process it twice
				}
			foreach ($this->_mapHash as $k => $v)
				if (isset($ref[$v])) {
					$out .= "$k  - " . $this->Escape($ref[$v]) . "\n";
					unset($ref[$v]); // Remove it from the reference copy so we dont process it twice
				}
			if (isset($ref['date']) && $date = $this->parent->ToDate($ref['date'], '/', true))
				$out .= "DP  - $date/\n";
			if (isset($ref['doi'])){
				$out .= "AID  - $doi [doi]/\n";
			}
			$out .= "\r\n";
		}
		return $out;
	}

	function SetContents($blob) {
		$blob = preg_replace('![ ]*(\n|\r\n)[ ]{6}!m', " ", $blob); // one line per field
		//if (!preg_match_all('!^PMID- (\d+)[\r\n](.*?)(^[\r\n]|\Z)!ms', $blob, $matches, PREG_SET_ORDER))
		//if (!preg_match_all('!(:?^\r\n|^\n)(.*?)(^\r\n|^\n|\Z)!ms', $blob, $matches, PREG_SET_ORDER))
		//	return;
		$records = preg_split('!\n\W+!', $blob);
		$recno = 0;
		foreach ($records as $record) {
			$recno++;
			//$ref = array('accession-num' => intval($match[1]));
			$ref = array();
			//var_dump($record);
			$rawref = array();
			preg_match_all('!^([A-Z0-9]{2,4})[ ]{0,2}- (.*)$!m', $record, $rawrefextracted, PREG_SET_ORDER);
			foreach ($rawrefextracted as $rawrefbit) {
                // key/val mappings
                if (isset($this->_mapHash[$rawrefbit[1]])) {
                    $ref[$this->_mapHash[$rawrefbit[1]]] = $rawrefbit[2];
                    continue;
                }

                // key/val(array) mappings
                if (isset($this->_mapHashArray[$rawrefbit[1]])) {
                    $ref[$this->_mapHashArray[$rawrefbit[1]]][] = $rawrefbit[2];
                    continue;
                }

                // unknowns go to $rawref to be handled later
                if (isset($rawref[$rawrefbit[1]])) {
                    if (!is_array($rawref[$rawrefbit[1]])) {
                        $rawref[$rawrefbit[1]] = array($rawref[$rawrefbit[1]]);
                    }
                    $rawref[$rawrefbit[1]][] = $rawrefbit[2];
                } else {
                    $rawref[$rawrefbit[1]] = $rawrefbit[2];
                }
			}

			// }}}
			// Dates {{{
			if (!empty($rawref['DP'])){
				if (substr($rawref['DP'], 0, 10) == 'undefined/') {
					// Pass
				} elseif (preg_match('!([0-9]{4})///!', $rawref['DP'], $date)) { // Just year
					$ref['year'] = $date[1];
					$ref['date'] = strtotime($date[1] . "-01-01");
				} elseif (preg_match('!([0-9]{4})/([0-9]{1,2})//!', $rawref['DP'], $date)) { // Just month
					$ref['year'] = $date[1];
					$ref['date'] = strtotime("{$date[1]}-{$date[2]}-01");
				} elseif (preg_match('!([0-9]{4}) ([0-9]{1,2})/([0-9]{1,2})/!', $rawref['DP'], $date)) { // Full date
					$ref['year'] = $date[1];
					$ref['date'] = strtotime("{$date[1]}-{$date[2]}-{$date[3]}");
				} elseif (preg_match('!([0-9]{4})[ ]?(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)?[ ]?([0-9]{1,2})?!', $rawref['DP'], $date)) {
					$ref['year'] = $date[1];
					$ref['date'] = strtotime(str_replace(array_keys($this->_mapMonths), array_values($this->_mapMonths), "{$date[1]}-". (!empty($date[2]) ? $date[2] : "01") . "-" .(!empty($date[3]) ? $date[3] : "01")));
				}
			}
			// }}}
			// DOI {{{
			if (!empty($rawref['LID'])){
				if (!is_array($rawref['LID'])){
					$rawref['LID'] = array($rawref['LID']);
				}
				foreach ($rawref['LID'] as $article_identifier) {
					$article_identifier = trim(preg_replace('/\s\s+/', ' ', $article_identifier));
					if (substr($article_identifier, -6) == " [doi]"){
						$ref['doi'] = substr($article_identifier, 0, strlen($article_identifier) -6);
					}
				}
			}
			if (!empty($rawref['AID'])){
				if (!is_array($rawref['AID'])){
					$rawref['AID'] = array($rawref['AID']);
				}
				foreach ($rawref['AID'] as $article_identifier) {
					$article_identifier = trim(preg_replace('/\s\s+/', ' ', $article_identifier));
					if (substr($article_identifier, -6) == " [doi]"){
						$ref['doi'] = substr($article_identifier, 0, strlen($article_identifier) -6);
					} else {
						$ref['custom7'] = $article_identifier;
					}
				}
			}
			// }}}
			// Publication Type {{{
			if (!empty($rawref['PT'])){
				if (!is_array($rawref['PT'])){
					$rawref['PT'] = array($rawref['PT']);
				}
				foreach ($rawref['PT'] as $publication_type) {
					$publication_type = trim(preg_replace('/\s\s+/', ' ', $publication_type));
					if (array_key_exists($publication_type, $this->_mapPublicationTypes)){
						$ref['type'] = $this->_mapPublicationTypes[$publication_type];
					}
				}
			}
			// }}}
			// Append to $this->parent->refs {{{
			if (!$this->parent->refId) { // Use indexed array
				$this->parent->refs[] = $ref;
			} elseif (is_string($this->parent->refId)) { // Use assoc array
				if ($this->parent->refId == 'rec-number') {
					$this->parent->$refs[$recno] = $ref;
				} elseif (!isset($ref[$this->parent->refId])) {
					trigger_error("No ID found in reference to use as key");
				} else {
					$this->parent->refs[$ref[$this->parent->refId]] = $ref;
				}
			}
			// }}}
		}
	}
}
