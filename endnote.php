<?
/**
* Simple class to read and write EndNote XML files
*
* @author Matt Carter <m@ttcarter.com>
*/
class PHPEndNote {
	/**
	* An indexed or hash array of references
	* Each refernce has the following keys:
	*	* authors - Array of authors
	*	* address - String (optional)
	*	* contact-name - String (optional)
	*	* contact-email - String (optional)
	*	* title - String
	*	* title-secondary - String (optional)
	*	* title-short - String (optional)
	*	* periodical-title - String (optional)
	* 	* pages - String (optional)
	*	* volume - String (optional)
	*	* number - String (optional)
	*	* section - String (optional)
	*	* year - String (optional) - FIXME: Explain format
	*	* date - String (optional) - FIXME: Explain format
	*	* abstract - String (optional)
	*	* url - String (optional)
	*	* notes - String (optional)
	*
	* @var array
	*/
	var $references;

	/**
	* Return the raw XML of the $references array
	* @see $references
	*/
	function GetXML() {
		$out = '<?xml version="1.0" encoding="UTF-8"?><xml><records>';
		$number = 0;
		foreach ($this->references as $id => $ref) {
			$out .= '<record>';
			$out .= '<database name="CREBP-SearchTool.enl" path="C:\CREBP-SearchTool.enl">CREBP-SearchTool.enl</database>';
			$out .= '<source-app name="EndNote" version="16.0">EndNote</source-app>';
			$out .= '<rec-number>' . $number . '</rec-number>';
			$out .= '<foreign-keys><key app="EN" db-id="s55prpsswfsepue0xz25pxai2p909xtzszzv">' . $number . '</key></foreign-keys>';
			$out .= '<ref-type name="Journal Article">17</ref-type>';

			$out .= '<contributors><authors>';
				foreach ($ref['authors'] as $author)
					$out .= '<author><style face="normal" font="default" size="100%">' . $author . '</style></author>';
			$out .= '</authors></contributors>';

			$out .= '<titles>';
				$out .= '<title><style face="normal" font="default" size="100%">' . $ref['title'] . '</style></title>';
				$out .= '<secondary-title><style face="normal" font="default" size="100%">' . (isset($ref['title-secondary']) && $ref['title-secondary'] ? $ref['title-secondary'] : '') . '</style></secondary-title>';
				$out .= '<short-title><style face="normal" font="default" size="100%">' . (isset($ref['title-short']) && $ref['title-short'] ? $ref['title-short'] : '') . '</style></short-title>';
			$out .= '</titles>';

				$out .= '<periodical><full-title><style face="normal" font="default" size="100%">' . (isset($ref['periodical-title']) && $ref['periodical-title'] ? $ref['periodical-title'] : '') . '</style></full-title></periodical>';

			// Simple key values
			foreach (array(
				'auth-address' => 'address',
				'pages' => 'pages',
				'volume' => 'volume',
				'number' => 'number',
				'section' => 'section',
				'abstract' => 'abstract',
				'isbn' => 'isbn',
			) as $enkey => $ourkey)
				$out .= "<$enkey><style face=\"normal\" font=\"default\" size=\"100%\">" . (isset($ref[$ourkey]) && $ref[$ourkey] ? $ref[$ourkey] : '') . "</style></$enkey>";

			$out .= '<dates>';
				$out .= '<year><style face="normal" font="default" size="100%">' . (isset($ref['year']) && $ref['year'] ? $ref['year'] : '') . '</style></year>';
				$out .= '<pub-dates><date><style face="normal" font="default" size="100%">' . (isset($ref['year']) && $ref['year'] ? $ref['year'] : '') . '</style></date></pub-dates>';
			$out .= '</dates>';

			$out .= '<urls><related-urls>';
				foreach ($urls as $url)
					$out .= '<url><style face="normal" font="default" size="100%">' . $url . '</style></url>';
			$out .= '</related-urls></urls>';
			$out .= '<research-notes><style face="normal" font="default" size="100%">' . (isset($ref['notes']) && $ref['notes'] ? $ref['notes'] : '') . '</style></research-notes>';

			$out .= '</record>';
			$number++;
		}
		echo '</records></xml>';
	}

	/**
	* Generate an XML file and output it to the browser
	* This will force the user to save the file somewhere to be opened later by EndNote
	* @param string $filename The default filename to save as
	*/
	function OutputXML($filename = 'EndNote.xml') {
		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		echo $this->GetXML();
	}
}
