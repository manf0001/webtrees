<?php
// Function for printing facts
//
// Various printing functions used to print fact records
//
// webtrees: Web based Family History software
// Copyright (C) 2013 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2002 to 2010  PGV Development Team.  All rights reserved.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id$

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

// Print a fact record, for the individual/family/source/repository/etc. pages.
//
// Although a WT_Fact has a parent object, we also need to know
// the WT_GedcomRecord for which we are printing it.  For example,
// we can show the death of X on the page of Y, or the marriage
// of X+Y on the page of Z.  We need to know both records to
// calculate ages, relationships, etc.
function print_fact(WT_Fact $fact, WT_GedcomRecord $record) {
	global $HIDE_GEDCOM_ERRORS, $SHOW_FACT_ICONS;
	static $n_chil=0, $n_gchi=0;

	if (!$fact->canShow()) {
		return;
	}

	$parent = $fact->getParent();

	// Some facts don't get printed here ...
	switch ($fact->getTag()) {
	case 'NOTE':
		print_main_notes($fact, 1);
		return;
	case 'SOUR':
		print_main_sources($fact, 1);
		return;
	case 'OBJE':
		print_main_media($fact, 1);
		return;
	case 'FAMC':
	case 'FAMS':
	case 'CHIL':
	case 'HUSB':
	case 'WIFE':
		// These are internal links, not facts
		return;
	case '_WT_OBJE_SORT':
		// These links are used internally to record the sort order.
		return;
	default:
		// Hide unrecognised/custom tags?
		if ($HIDE_GEDCOM_ERRORS && !WT_Gedcom_Tag::isTag($fact->getTag())) {
			return;
		}
		break;
	}

	// Who is this fact about?  Need it to translate fact label correctly
	if ($fact->getParent() instanceof WT_Family && $fact->getParent()->getSpouse($record)) {
		// Event of close relative
		$label_person = $fact->getParent()->getSpouse($record);
	} else if ($parent instanceof WT_Family) {
		// Family event
		$husb = $parent->getHusband();
		$wife = $parent->getWife();
		if (empty($wife) && !empty($husb)) $label_person=$husb;
		else if (empty($husb) && !empty($wife)) $label_person=$wife;
		else $label_person=$parent;
	} else {
		// The actual person
		$label_person=$parent;
	}

	// New or deleted facts need different styling
	$styleadd='';
	if ($fact->isNew()) {
		$styleadd = 'new';
	}
	if ($fact->isOld()) {
		$styleadd = 'old';
	}

	// Event of close relative
	if (preg_match('/^_[A-Z_]{4,5}_[A-Z0-9]{4}$/', $fact->getTag())) {
		$styleadd='rela';
	}

	// Event of close associates
	if ($fact->getFactId()=='asso') {
		$styleadd='rela';
	}

	// historical facts
	if ($fact->getFactId()=='histo') {
		$styleadd='histo';
	}

	// Does this fact have a type?
	if (preg_match('/\n2 TYPE (.+)/', $fact->getGedcom(), $match)) {
		$type=$match[1];
	} else {
		$type='';
	}

	switch ($fact->getTag()) {
	case 'EVEN':
	case 'FACT':
		if (WT_Gedcom_Tag::isTag($type)) {
			// Some users (just Meliza?) use "1 EVEN/2 TYPE BIRT".  Translate the TYPE.
			$label=WT_Gedcom_Tag::getLabel($type, $label_person);
			$type=''; // Do not print this again
		} elseif ($type) {
			// We don't have a translation for $type - but a custom translation might exist.
			$label=WT_I18N::translate(htmlspecialchars($type));
			$type=''; // Do not print this again
		} else {
			// An unspecified fact/event
			$label=WT_Gedcom_Tag::getLabel($fact->getTag(), $label_person);
		}
		break;
	case 'MARR':
		// This is a hack for a proprietory extension.  Is it still used/needed?
		$utype = strtoupper($type);
		if ($utype=='CIVIL' || $utype=='PARTNERS' || $utype=='RELIGIOUS') {
			$label=WT_Gedcom_Tag::getLabel('MARR_'.$utype, $label_person);
			$type=''; // Do not print this again
		} else {
			$label=WT_Gedcom_Tag::getLabel($fact->getTag(), $label_person);
		}
		break;
	default:
		// Normal fact/event
		$label=WT_Gedcom_Tag::getLabel($fact->getTag(), $label_person);
		break;
	}

	echo '<tr class="', $styleadd, '">';
	echo '<td class="descriptionbox width20">';

	if ($SHOW_FACT_ICONS) {
		echo $fact->Icon(), ' ';
	}

	if ($fact->canEdit()) {
		?>
		<a
			href="#"
			title="<?php echo WT_I18N::translate('Edit'); ?>"
			onclick="return edit_record('<?php echo $parent->getXref(); ?>', '<?php echo $fact->getFactId(); ?>');"
		><?php echo $label; ?></a>
		<div class="editfacts">
			<div class="editlink">
				<a
					href="#"
					title="<?php echo WT_I18N::translate('Edit'); ?>"
					class="editicon"
					onclick="return edit_record('<?php echo $parent->getXref(); ?>', '<?php echo $fact->getFactId(); ?>');"
				><span class="link_text"><?php echo WT_I18N::translate('Edit'); ?></span></a>
			</div>
			<div class="copylink">
				<a
					href="#"
					title="<?php echo WT_I18N::translate('Copy'); ?>"
					class="copyicon"
					onclick="return copy_fact('<?php echo $parent->getXref(); ?>', '<?php echo $fact->getFactId(); ?>');"
				><span class="link_text">', WT_I18N::translate('Copy'), '</span></a>
			</div>
			<div class="deletelink">
				<a
					href="#"
					title="<?php echo WT_I18N::translate('Delete'); ?>"
					class="deleteicon"
					onclick="return delete_fact('<?php echo WT_I18N::translate('Are you sure you want to delete this fact?'); ?>', '<?php echo $parent->getXref(); ?>', '<?php echo $fact->getFactId(); ?>');"
				><span class="link_text">', WT_I18N::translate('Delete'), '</span></a>
			</div>
		</div>
		<?php
	} else {
		echo $label;
	}

	switch ($fact->getTag()) {
	case '_BIRT_CHIL':
		echo '<br>', WT_I18N::translate('#%d', ++$n_chil);
		break;
	case '_BIRT_GCHI':
	case '_BIRT_GCH1':
	case '_BIRT_GCH2':
		echo '<br>', WT_I18N::translate('#%d', ++$n_gchi);
		break;
	}

	echo '</td><td class="optionbox ', $styleadd, ' wrap">';

	// Print the spouse and family of this fact/event
	if ($parent instanceof WT_Family && $record instanceof WT_Individual) {
		foreach ($parent->getSpouses() as $spouse) {
			if (!$record->equals($spouse)) {
				echo '<a href="', $spouse->getHtmlUrl(), '">', $spouse->getFullName(), '</a> - ';
			}
		}
		// Family events on an individual page
		echo '<a href="', $parent->getHtmlUrl(), '">', WT_I18N::translate('View Family'), '</a><br>';
	}

	// Print the value of this fact/event
	switch ($fact->getTag()) {
	case 'ADDR':
		print_address_structure($fact->getGedcom(), 1);
		break;
	case 'AFN':
		echo '<div class="field"><a href="https://familysearch.org/search/tree/results#count=20&query=afn:', rawurlencode($fact->getValue()), '" target="new">', htmlspecialchars($fact->getValue()), '</a></div>';
		break;
	case 'ASSO':
		// we handle this later, in print_asso_rela_record()
		break;
	case 'EMAIL':
	case 'EMAI':
	case '_EMAIL':
		echo '<div class="field"><a href="mailto:', htmlspecialchars($fact->getValue()), '">', htmlspecialchars($fact->getValue()), '</a></div>';
		break;
	case 'FILE':
		if (WT_USER_CAN_EDIT || WT_USER_CAN_ACCEPT) {
			echo '<div class="field">', htmlspecialchars($fact->getValue()), '</div>';
		}
		break;
	case 'RESN':
		echo '<div class="field">';
		switch ($fact->getValue()) {
		case 'none':
			// Note: "1 RESN none" is not valid gedcom.
			// However, webtrees privacy rules will interpret it as "show an otherwise private record to public".
			echo '<i class="icon-resn-none"></i> ', WT_I18N::translate('Show to visitors');
			break;
		case 'privacy':
			echo '<i class="icon-class-none"></i> ', WT_I18N::translate('Show to members');
			break;
		case 'confidential':
			echo '<i class="icon-confidential-none"></i> ', WT_I18N::translate('Show to managers');
			break;
		case 'locked':
			echo '<i class="icon-locked-none"></i> ', WT_I18N::translate('Only managers can edit');
			break;
		default:
			echo htmlspecialchars($fact->getValue());
			break;
		}
		echo '</div>';
		break;
	case 'PUBL': // Publication details might contain URLs.
		echo '<div class="field">', expand_urls(htmlspecialchars($fact->getValue())), '</div>';
		break;
	case 'REPO':
		if (preg_match('/^@('.WT_REGEX_XREF.')@$/', $fact->getValue(), $match)) {
			print_repository_record($match[1]);
		} else {
			echo '<div class="error">', htmlspecialchars($fact->getValue()), '</div>';
		}
		break;
	case 'URL':
	case 'WWW':
		echo '<div class="field"><a href="', htmlspecialchars($fact->getValue()), '">', htmlspecialchars($fact->getValue()), '</a></div>';
		break;
	case 'TEXT': // 0 SOUR / 1 TEXT
		echo '<div class="field">', nl2br(htmlspecialchars($fact->getValue()), false), '</div>';
		break;
	default:
		// Display the value for all other facts/events
		switch ($fact->getValue()) {
		case '':
			// Nothing to display
			break;
		case 'N':
			// Not valid GEDCOM
			echo '<div class="field">', WT_I18N::translate('No'), '</div>';
			break;
		case 'Y':
			// Do not display "Yes".
			break;
		default:
			if (preg_match('/^@('.WT_REGEX_XREF.')@$/', $fact->getValue(), $match)) {
				$target=WT_GedcomRecord::getInstance($match[1]);
				if ($target) {
					echo '<div><a href="', $target->getHtmlUrl(), '">', $target->getFullName(), '</a></div>';
				} else {
					echo '<div class="error">', htmlspecialchars($fact->getValue()), '</div>';
				}
			} else {
				echo '<div class="field"><span dir="auto">', htmlspecialchars($fact->getValue()), '</span></div>';
			}
			break;
		}
		break;
	}

	// Print the type of this fact/event
	if ($type) {
		// We don't have a translation for $type - but a custom translation might exist.
		echo WT_Gedcom_Tag::getLabelValue('TYPE', WT_I18N::translate(htmlspecialchars($type)));
	}

	// Print the date of this fact/event
	echo format_fact_date($fact, $record, true, true);
	
	// Print the place of this fact/event
	echo '<div class="place">', format_fact_place($fact, true, true, true), '</div>';
	// A blank line between the primary attributes (value, date, place) and the secondary ones
	echo '<br>';
	print_address_structure($fact->getGedcom(), 2);

	// Print the associates of this fact/event
	print_asso_rela_record($fact, $record);

	// Print any other "2 XXXX" attributes, in the order in which they appear.
	preg_match_all('/\n2 ('.WT_REGEX_TAG.') (.+)/', $fact->getGedcom(), $matches, PREG_SET_ORDER);
	foreach ($matches as $match) {
		switch ($match[1]) {
		case 'DATE':
		case 'TIME':
		case 'AGE':
		case 'PLAC':
		case 'ADDR':
		case 'ALIA':
		case 'ASSO':
		case '_ASSO':
		case 'DESC':
		case 'RELA':
		case 'STAT':
		case 'TEMP':
		case 'TYPE':
		case 'FAMS':
		case 'CONT':
			// These were already shown at the beginning
			break;
		case 'NOTE':
		case 'OBJE':
		case 'SOUR':
			// These will be shown at the end
			break;
		case 'EVEN': // 0 SOUR / 1 DATA / 2 EVEN / 3 DATE / 3 PLAC
			$events=array();
			foreach (preg_split('/ *, */', $match[2]) as $event) {
				$events[]=WT_Gedcom_Tag::getLabel($event);
			}
			if (count($events)==1) echo WT_Gedcom_Tag::getLabelValue('EVEN', $event);
			else echo WT_Gedcom_Tag::getLabelValue('EVEN', implode(WT_I18N::$list_separator, $events));
			if (preg_match('/\n3 DATE (.+)/', $fact->getGedcom(), $date_match)) {
				$date=new WT_Date($date_match[1]);
				echo WT_Gedcom_Tag::getLabelValue('DATE', $date->Display());
			}
			if (preg_match('/\n3 PLAC (.+)/', $fact->getGedcom(), $plac_match)) {
				echo WT_Gedcom_Tag::getLabelValue('PLAC', $plac_match[1]);
			}
			break;
		case 'FAMC': // 0 INDI / 1 ADOP / 2 FAMC / 3 ADOP
			$family=WT_Family::getInstance(str_replace('@', '', $match[2]));
			if ($family) { // May be a pointer to a non-existant record
				echo WT_Gedcom_Tag::getLabelValue('FAM', '<a href="'.$family->getHtmlUrl().'">'.$family->getFullName().'</a>');
				if (preg_match('/\n3 ADOP (HUSB|WIFE|BOTH)/', $fact->getGedcom(), $match)) {
					echo WT_Gedcom_Tag::getLabelValue('ADOP', WT_Gedcom_Code_Adop::getValue($match[1], $label_person));
				}
			} else {
				echo WT_Gedcom_Tag::getLabelValue('FAM', '<span class="error">'.$match[2].'</span>');
			}
			break;
		case '_WT_USER':
			$fullname=getUserFullname(get_user_id($match[2])); // may not exist	
			if ($fullname) {
				echo WT_Gedcom_Tag::getLabelValue('_WT_USER', $fullname);
			} else {
				echo WT_Gedcom_Tag::getLabelValue('_WT_USER', htmlspecialchars($match[2]));
			}
			break;
		case 'RESN':
			switch ($match[2]) {
			case 'none':
				// Note: "2 RESN none" is not valid gedcom.
				// However, webtrees privacy rules will interpret it as "show an otherwise private fact to public".
				echo WT_Gedcom_Tag::getLabelValue('RESN', '<i class="icon-resn-none"></i> '.WT_I18N::translate('Show to visitors'));
				break;
			case 'privacy':
				echo WT_Gedcom_Tag::getLabelValue('RESN', '<i class="icon-resn-privacy"></i> '.WT_I18N::translate('Show to members'));
				break;
			case 'confidential':
				echo WT_Gedcom_Tag::getLabelValue('RESN', '<i class="icon-resn-confidential"></i> '.WT_I18N::translate('Show to managers'));
				break;
			case 'locked':
				echo WT_Gedcom_Tag::getLabelValue('RESN', '<i class="icon-resn-locked"></i> '.WT_I18N::translate('Only managers can edit'));
				break;
			default:
				echo WT_Gedcom_Tag::getLabelValue('RESN', htmlspecialchars($match[2]));
				break;
			}
			break;
		case 'CALN':
			echo WT_Gedcom_Tag::getLabelValue('CALN', expand_urls($match[2]));
			break;
		case 'FORM': // 0 OBJE / 1 FILE / 2 FORM / 3 TYPE
			echo WT_Gedcom_Tag::getLabelValue('FORM', $match[2]);
			if (preg_match('/\n3 TYPE (.+)/', $fact->getGedcom(), $type_match)) {
				echo WT_Gedcom_Tag::getLabelValue('TYPE', WT_Gedcom_Tag::getFileFormTypeValue($type_match[1]));
			}
			break;
		default:
			if (!$HIDE_GEDCOM_ERRORS || WT_Gedcom_Tag::isTag($match[1])) {
				if (preg_match('/^@(' . WT_REGEX_XREF . ')@$/', $match[2], $xmatch)) {
					// Links
					$linked_record = WT_GedcomRecord::getInstance($xmatch[1]);
					if ($linked_record) {
						$link = '<a href="' .$linked_record->getHtmlUrl()  . '">' . $linked_record->getFullName() . '</a>';
						echo WT_Gedcom_Tag::getLabelValue($fact->getTag().':'.$match[1], $link);
					} else {
						echo WT_Gedcom_Tag::getLabelValue($fact->getTag().':'.$match[1], htmlspecialchars($match[2]));
					}
				} else {
					// Non links
					echo WT_Gedcom_Tag::getLabelValue($fact->getTag().':'.$match[1], htmlspecialchars($match[2]));
				}
			}
			break;
		}
	}
	// -- find source for each fact
	print_fact_sources($fact->getGedcom(), 2);
	// -- find notes for each fact
	print_fact_notes($fact->getGedcom(), 2);
	//-- find media objects
	print_media_links($fact->getGedcom(), 2, $parent->getXref());
	echo '</td></tr>';
}
//------------------- end print fact function

/**
 * print a repository record
 *
 * find and print repository information attached to a source
 * @param string $sid  the Gedcom Xref ID of the repository to print
 */
function print_repository_record($xref) {
	$repository=WT_Repository::getInstance($xref);
	if ($repository && $repository->canShow()) {
		echo '<a class="field" href="', $repository->getHtmlUrl(), '">', $repository->getFullName(), '</a><br>';
		print_address_structure($repository->getGedcom(), 1);
		echo '<br>';
		print_fact_notes($repository->getGedcom(), 1);
	}
}

/**
 * print a source linked to a fact (2 SOUR)
 *
 * this function is called by the print_fact function and other functions to
 * print any source information attached to the fact
 * @param string $factrec The fact record to look for sources in
 * @param int $level The level to look for sources at
 * @param boolean $return whether to return the data or print the data
 */
function print_fact_sources($factrec, $level, $return=false) {
	global $EXPAND_SOURCES;

	$data = '';
	$nlevel = $level+1;

	// -- Systems not using source records [ 1046971 ]
	$ct = preg_match_all("/$level SOUR (.*)/", $factrec, $match, PREG_SET_ORDER);
	for ($j=0; $j<$ct; $j++) {
		if (strpos($match[$j][1], '@')===false) {
			$srec = get_sub_record($level, "$level SOUR ", $factrec, $j+1);
			$srec = substr($srec, 6); // remove "2 SOUR"
			$srec = str_replace("\n".($level+1)." CONT ", '<br>', $srec); // remove n+1 CONT
			$data .= '<div="fact_SOUR"><span class="label">'.WT_I18N::translate('Source').':</span> <span class="field" dir="auto">'.htmlspecialchars($srec).'</span></div>';
		}
	}
	// -- find source for each fact
	$ct = preg_match_all("/$level SOUR @(.*)@/", $factrec, $match, PREG_SET_ORDER);
	$spos2 = 0;
	for ($j=0; $j<$ct; $j++) {
		$sid = $match[$j][1];
		$source=WT_Source::getInstance($sid);
		if ($source) {
			if ($source->canShow()) {
				$spos1 = strpos($factrec, "$level SOUR @".$sid."@", $spos2);
				$spos2 = strpos($factrec, "\n$level", $spos1);
				if (!$spos2) {
					$spos2 = strlen($factrec);
				}
				$srec = substr($factrec, $spos1, $spos2-$spos1);
				$lt = preg_match_all("/$nlevel \w+/", $srec, $matches);
				$data .= '<div class="fact_SOUR">';
				$data .= '<span class="label">';
				$elementID = $sid."-".(int)(microtime()*1000000);
				if ($EXPAND_SOURCES) {
					$plusminus='icon-minus';
				} else {
					$plusminus='icon-plus';
				}
				if ($lt>0) {
					$data .= '<a href="#" onclick="return expand_layer(\''.$elementID.'\');"><i id="'.$elementID.'_img" class="'.$plusminus.'"></i></a> ';
				}
				$data .= WT_I18N::translate('Source').':</span> <span class="field">';
				$data .= '<a href="'.$source->getHtmlUrl().'">'.$source->getFullName().'</a>';
				$data .= '</span></div>';
	
				$data .= "<div id=\"$elementID\"";
				if ($EXPAND_SOURCES) {
					$data .= ' style="display:block"';
				}
				$data .= ' class="source_citations">';
				// PUBL
				$text = get_gedcom_value('PUBL', '1', $source->getGedcom());
				if (!empty($text)) {
					$data .= '<span class="label">'.WT_Gedcom_Tag::getLabel('PUBL').': </span>';
					$data .= $text;
				}
				$data .= printSourceStructure(getSourceStructure($srec));
				$data .= '<div class="indent">';
				ob_start();
				print_media_links($srec, $nlevel);
				$data .= ob_get_clean();
				$data .= print_fact_notes($srec, $nlevel, false, true);
				$data .= '</div>';
				$data .= '</div>';
			} else {
				// Show that we do actually have sources for this data.
				// Commented out for now, based on user feedback.
				// http://webtrees.net/index.php/en/forum/3-help-for-beta-and-svn-versions/27002-source-media-privacy-issue
				//$data .= WT_Gedcom_Tag::getLabelValue('SOUR', WT_I18N::translate('yes'));
			}
		} else {
			$data .= WT_Gedcom_Tag::getLabelValue('SOUR', '<span class="error">'.$sid.'</span>');
		}
	}

	if ($return) {
		return $data;
	} else {
		echo $data;
	}	
}

//-- Print the links to media objects
function print_media_links($factrec, $level, $pid='') {
	global $TEXT_DIRECTION;
	global $SEARCH_SPIDER;
	global $THUMBNAIL_WIDTH;
	global $GEDCOM, $HIDE_GEDCOM_ERRORS;

	$ged_id=get_id_from_gedcom($GEDCOM);
	$nlevel = $level+1;
	if ($level==1) $size=50;
	else $size=25;
	if (preg_match_all("/$level OBJE @(.*)@/", $factrec, $omatch, PREG_SET_ORDER) == 0) return;
	$objectNum = 0;
	while ($objectNum < count($omatch)) {
		$media_id = $omatch[$objectNum][1];
		$media=WT_Media::getInstance($media_id);
		if ($media) {
			if ($media->canShow()) {
				if ($objectNum > 0) echo '<br class="media-separator" style="clear:both;">';
				echo '<div class="media-display"><div class="media-display-image">';
				echo $media->displayImage();
				echo '</div>'; // close div "media-display-image"
				echo '<div class="media-display-title">';
				if ($SEARCH_SPIDER) {
					echo $media->getFullName();
				} else {
					echo '<a href="mediaviewer.php?mid=', $media->getXref(), '&amp;ged=', WT_GEDURL, '">', $media->getFullName(), '</a>';
				}
				// NOTE: echo the notes of the media
				echo '<p>';
				echo print_fact_notes($media->getGedcom(), 1);
				if (preg_match('/2 DATE (.+)/', get_sub_record('FILE', 1, $media->getGedcom()), $match)) {
					$media_date=new WT_Date($match[1]);
					$md = $media_date->Display(true);
					echo '<p class="label">', WT_Gedcom_Tag::getLabel('DATE'), ': </p> ', $md;
				}
				$ttype = preg_match("/".($nlevel+1)." TYPE (.*)/", $media->getGedcom(), $match);
				if ($ttype>0) {
					$mediaType = WT_Gedcom_Tag::getFileFormTypeValue($match[1]);
					echo '<p class="label">', WT_I18N::translate('Type'), ': </span> <span class="field">', $mediaType, '</p>';
				}
				echo '</p>';
				//-- print spouse name for marriage events
				$ct = preg_match("/WT_SPOUSE: (.*)/", $factrec, $match);
				if ($ct>0) {
					$spouse=WT_Individual::getInstance($match[1]);
					if ($spouse) {
						echo '<a href="', $spouse->getHtmlUrl(), '">';
						echo $spouse->getFullName();
						echo '</a>';
					}
					if (empty($SEARCH_SPIDER)) {
						$ct = preg_match("/WT_FAMILY_ID: (.*)/", $factrec, $match);
						if ($ct>0) {
							$famid = trim($match[1]);
							$family = WT_Family::getInstance($famid);
							if ($family) {
								if ($spouse) echo " - ";
								echo '<a href="', $family->getHtmlUrl(), '">', WT_I18N::translate('View Family'), '</a>';
							}
						}
					}
				}
				print_fact_notes($media->getGedcom(), $nlevel);
				print_fact_sources($media->getGedcom(), $nlevel);
				echo '</div>';//close div "media-display-title"
				echo '</div>';//close div "media-display"
			}
		} elseif (!$HIDE_GEDCOM_ERRORS) {
			echo '<p class="ui-state-error">', $media_id, '</p>';
		}
		$objectNum ++;
	}
}
/**
 * print an address structure
 *
 * takes a gedcom ADDR structure and prints out a human readable version of it.
 * @param string $factrec The ADDR subrecord
 * @param int $level The gedcom line level of the main ADDR record
 */
function print_address_structure($factrec, $level) {
	if (preg_match("/$level ADDR (.*)/", $factrec, $omatch)) {
		$arec = get_sub_record($level, "$level ADDR", $factrec, 1);
		$cont = get_cont($level+1, $arec);
		$resultText = $omatch[1] . get_cont($level+1, $arec);
		if ($level>1) {
			$resultText = '<span class="label">'.WT_Gedcom_Tag::getLabel('ADDR').': </span><br><div class="indent">' . $resultText . '</div>';
		}
		echo $resultText;
	}
}

// Print a row for the sources tab on the individual page
function print_main_sources(WT_Fact $fact, $level) {
	global $SHOW_FACT_ICONS;

	$factrec = $fact->getGedcom();
	$fact_id = $fact->getFactId();
	$parent  = $fact->getParent();
	$pid     = $parent->getXref();
	
	$nlevel = $level+1;
	if ($fact->isNew()) {
		$styleadd = 'new';
		$can_edit = $level==1 && $fact->canEdit();
	} elseif ($fact->isOld()) {
		$styleadd='old';
		$can_edit = false;
	} else {
		$styleadd='';
		$can_edit = $level==1 && $fact->canEdit();
	}

	// -- find source for each fact
	$ct = preg_match_all("/$level SOUR @(.*)@/", $factrec, $match, PREG_SET_ORDER);
	$spos2 = 0;
	for ($j=0; $j<$ct; $j++) {
		$sid = $match[$j][1];
		$spos1 = strpos($factrec, "$level SOUR @".$sid."@", $spos2);
		$spos2 = strpos($factrec, "\n$level", $spos1);
		if (!$spos2) $spos2 = strlen($factrec);
		$srec = substr($factrec, $spos1, $spos2-$spos1);
		$source=WT_Source::getInstance($sid);
		// Allow access to "1 SOUR @non_existent_source@", so it can be corrected/deleted
		if (!$source || $source->canShow()) {
			if ($level>1) echo '<tr class="row_sour2">';
			else echo '<tr>';
			echo '<td class="descriptionbox';
			if ($level>1) echo ' rela';
			echo ' ', $styleadd, ' width20">';
			$temp = preg_match("/^\d (\w*)/", $factrec, $factname);
			$factlines = explode("\n", $factrec); // 1 BIRT Y\n2 SOUR ...
			$factwords = explode(" ", $factlines[0]); // 1 BIRT Y
			$factname = $factwords[1]; // BIRT
			if ($factname == 'EVEN' || $factname=='FACT') {
				// Add ' EVEN' to provide sensible output for an event with an empty TYPE record
				$ct = preg_match("/2 TYPE (.*)/", $factrec, $ematch);
				if ($ct>0) {
					$factname = trim($ematch[1]);
					echo $factname;
				} else {
					echo WT_Gedcom_Tag::getLabel($factname, $parent);
				}
			} else
			if ($can_edit) {
				echo "<a onclick=\"return edit_record('$pid', '$fact_id');\" href=\"#\" title=\"", WT_I18N::translate('Edit'), '">';
					if ($SHOW_FACT_ICONS) {
						if ($level==1) echo '<i class="icon-source"></i> ';
					}
					echo WT_Gedcom_Tag::getLabel($factname, $parent), '</a>';
					echo '<div class="editfacts">';
					echo "<div class=\"editlink\"><a class=\"editicon\" onclick=\"return edit_record('$pid', '$fact_id');\" href=\"#\" title=\"".WT_I18N::translate('Edit')."\"><span class=\"link_text\">".WT_I18N::translate('Edit')."</span></a></div>";
					echo '<div class="copylink"><a class="copyicon" href="#" onclick="jQuery.post(\'action.php\',{action:\'copy-fact\', type:\'\', factgedcom:\''.rawurlencode($factrec).'\'},function(){location.reload();})" title="'.WT_I18N::translate('Copy').'"><span class="link_text">'.WT_I18N::translate('Copy').'</span></a></div>';
					echo "<div class=\"deletelink\"><a class=\"deleteicon\" onclick=\"return delete_fact('$pid', '$fact_id', '', '".WT_I18N::translate('Are you sure you want to delete this fact?')."');\" href=\"#\" title=\"".WT_I18N::translate('Delete')."\"><span class=\"link_text\">".WT_I18N::translate('Delete')."</span></a></div>";
				echo '</div>';
			} else {
				echo WT_Gedcom_Tag::getLabel($factname, $parent);
			}
			echo '</td>';
			echo '<td class="optionbox ', $styleadd, ' wrap">';
			if ($source) {
				echo '<a href="', $source->getHtmlUrl(), '">', $source->getFullName(), '</a>';
				// PUBL
				$text = get_gedcom_value('PUBL', '1', $source->getGedcom());
				if (!empty($text)) {
					echo '<br><span class="label">', WT_Gedcom_Tag::getLabel('PUBL'), ': </span>';
					echo $text;
				}
				// 2 RESN tags.  Note, there can be more than one, such as "privacy" and "locked"
				if (preg_match_all("/\n2 RESN (.+)/", $factrec, $rmatches)) {
					foreach ($rmatches[1] as $rmatch) {
						echo '<br><span class="label">', WT_Gedcom_Tag::getLabel('RESN'), ':</span> <span class="field">';
						switch ($rmatch) {
						case 'none':
							// Note: "2 RESN none" is not valid gedcom, and the GUI will not let you add it.
							// However, webtrees privacy rules will interpret it as "show an otherwise private fact to public".
							echo '<i class="icon-resn-none"></i> ', WT_I18N::translate('Show to visitors');
							break;
						case 'privacy':
							echo '<i class="icon-resn-privacy"></i> ', WT_I18N::translate('Show to members');
							break;
						case 'confidential':
							echo '<i class="icon-resn-confidential"></i> ', WT_I18N::translate('Show to managers');
							break;
						case 'locked':
							echo '<i class="icon-resn-locked"></i> ', WT_I18N::translate('Only managers can edit');
							break;
						default:
							echo $rmatch;
							break;
						}
						echo '</span>';
					}
				}
				$cs = preg_match("/$nlevel EVEN (.*)/", $srec, $cmatch);
				if ($cs>0) {
					echo '<br><span class="label">', WT_Gedcom_Tag::getLabel('EVEN'), ' </span><span class="field">', $cmatch[1], '</span>';
					$cs = preg_match("/".($nlevel+1)." ROLE (.*)/", $srec, $cmatch);
					if ($cs>0) echo '<br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="label">', WT_Gedcom_Tag::getLabel('ROLE'), ' </span><span class="field">', $cmatch[1], '</span>';
				}
				echo printSourceStructure(getSourceStructure($srec));
				echo '<div class="indent">';
				print_media_links($srec, $nlevel);
				if ($nlevel==2) {
					print_media_links($source->getGedcom(), 1);
				}
				print_fact_notes($srec, $nlevel);
				if ($nlevel==2) {
					print_fact_notes($source->getGedcom(), 1);
				}
				echo '</div>';
			} else {
				echo $sid;
			}
			echo '</td></tr>';
		}
	}
}

/**
 * Print SOUR structure
 *
 *  This function prints the input array of SOUR sub-records built by the
 *  getSourceStructure() function.
 */
function printSourceStructure($textSOUR) {
	$html='';

	if ($textSOUR['PAGE']) {
		$html.='<div class="indent"><span class="label">'.WT_Gedcom_Tag::getLabel('PAGE').':</span> <span class="field" dir="auto">'.expand_urls($textSOUR['PAGE']).'</span></div>';
	}

	if ($textSOUR['EVEN']) {
		$html.='<div class="indent"><span class="label">'.WT_Gedcom_Tag::getLabel('EVEN').': </span><span class="field" dir="auto">'.$textSOUR['EVEN'].'</span></div>';
		if ($textSOUR['ROLE']) {
			$html.='<div class="indent"><span class="label">'.WT_Gedcom_Tag::getLabel('ROLE').': </span><span class="field" dir="auto">'.$textSOUR['ROLE'].'</span></div>';
		}
	}

	if ($textSOUR['DATE'] || count($textSOUR['TEXT'])) {
		if ($textSOUR['DATE']) {
			$date=new WT_Date($textSOUR['DATE']);
			$html.='<div class="indent"><span class="label">'.WT_Gedcom_Tag::getLabel('DATA:DATE').':</span> <span class="field">'.$date->Display(false).'</span></div>';
		}
		foreach ($textSOUR['TEXT'] as $text) {
			$html.='<div class="indent"><span class="label">'.WT_Gedcom_Tag::getLabel('TEXT').':</span> <span class="field" dir="auto">'.expand_urls($text).'</span></div>';
		}
	}

	if ($textSOUR['QUAY']!='') {
		$html.='<div class="indent"><span class="label">'.WT_Gedcom_Tag::getLabel('QUAY').':</span> <span class="field" dir="auto">'.WT_Gedcom_Code_Quay::getValue($textSOUR['QUAY']).'</span></div>';
	}

	return $html;
}

/**
 * Extract SOUR structure from the incoming Source sub-record
 *
 * The output array is defined as follows:
 *  $textSOUR['PAGE'] = Source citation
 *  $textSOUR['EVEN'] = Event type
 *  $textSOUR['ROLE'] = Role in event
 *  $textSOUR['DATA'] = place holder (no text in this sub-record)
 *  $textSOUR['DATE'] = Entry recording date
 *  $textSOUR['TEXT'] = (array) Text from source
 *  $textSOUR['QUAY'] = Certainty assessment
 */
function getSourceStructure($srec) {
	// Set up the output array
	$textSOUR=array(
		'PAGE'=>'',
		'EVEN'=>'',
		'ROLE'=>'',
		'DATA'=>'',
		'DATE'=>'',
		'TEXT'=>array(),
		'QUAY'=>'',
	);

	if ($srec) {
		$subrecords=explode("\n", $srec);
		for ($i=0; $i<count($subrecords); $i++) {
			$level=substr($subrecords[$i], 0, 1);
			$tag  =substr($subrecords[$i], 2, 4);
			$text =substr($subrecords[$i], 7);
			$i++;
			for (; $i<count($subrecords); $i++) {
				$nextTag = substr($subrecords[$i], 2, 4);
				if ($nextTag!='CONT') {
					$i--;
					break;
				}
				if ($nextTag=='CONT') $text .= '<br>';
				$text .= rtrim(substr($subrecords[$i], 7));
			}
			if ($tag=='TEXT') {
				$textSOUR[$tag][] = $text;
			} else {
				$textSOUR[$tag] = $text;
			}
		}
	}

	return $textSOUR;
}

// Print a row for the notes tab on the individual page
function print_main_notes(WT_Fact $fact, $level) {
	global $GEDCOM, $SHOW_FACT_ICONS, $TEXT_DIRECTION;

	$factrec = $fact->getGedcom();
	$fact_id = $fact->getFactId();
	$parent  = $fact->getParent();
	$pid     = $parent->getXref();

	if ($fact->isNew()) {
		$styleadd = ' new';
		$can_edit = $level==1 && $fact->canEdit();
	} elseif ($fact->isOld()) {
		$styleadd=' old';
		$can_edit = false;
	} else {
		$styleadd='';
		$can_edit = $level==1 && $fact->canEdit();
	}

	$ct = preg_match_all("/$level NOTE(.*)/", $factrec, $match, PREG_SET_ORDER);
	for ($j=0; $j<$ct; $j++) {
		if ($level>=2) echo '<tr class="row_note2">';
		else echo '<tr>';
		echo '<td valign="top" class="descriptionbox';
		if ($level>=2) echo ' rela';
		echo ' ', $styleadd, ' width20">';
		if ($can_edit) {
			echo '<a onclick="return edit_record(\'', $pid, '\', \'', $fact_id, '\');" href="#" title="', WT_I18N::translate('Edit'), '">';
			if ($level<2) {
				if ($SHOW_FACT_ICONS) {
					echo '<i class="icon-note"></i> ';
				}
				if (strstr($factrec, "1 NOTE @" )) {
					echo WT_Gedcom_Tag::getLabel('SHARED_NOTE');
				} else {
					echo WT_Gedcom_Tag::getLabel('NOTE');
				}
				echo '</a>';
				echo '<div class="editfacts">';
				echo "<div class=\"editlink\"><a class=\"editicon\" onclick=\"return edit_record('$pid', '$fact_id');\" href=\"#\" title=\"".WT_I18N::translate('Edit')."\"><span class=\"link_text\">".WT_I18N::translate('Edit')."</span></a></div>";
				echo '<div class="copylink"><a class="copyicon" href="#" onclick="jQuery.post(\'action.php\',{action:\'copy-fact\', type:\'\', factgedcom:\''.rawurlencode($factrec).'\'},function(){location.reload();})" title="'.WT_I18N::translate('Copy').'"><span class="link_text">'.WT_I18N::translate('Copy').'</span></a></div>';
				echo "<div class=\"deletelink\"><a class=\"deleteicon\" onclick=\"return delete_fact('".WT_I18N::translate('Are you sure you want to delete this fact?')."', '$pid', '$fact_id');\" href=\"#\" title=\"".WT_I18N::translate('Delete')."\"><span class=\"link_text\">".WT_I18N::translate('Delete')."</span></a></div>";
				echo '</div>';
			}
		} else {
			if ($level<2) {
				if ($SHOW_FACT_ICONS) {
					echo '<i class="icon-note"></i> ';
				}
				if (strstr($factrec, "1 NOTE @" )) {
					echo WT_Gedcom_Tag::getLabel('SHARED_NOTE');
				} else {
					echo WT_Gedcom_Tag::getLabel('NOTE');
				}
			}
			$factlines = explode("\n", $factrec); // 1 BIRT Y\n2 NOTE ...
			$factwords = explode(" ", $factlines[0]); // 1 BIRT Y
			$factname = $factwords[1]; // BIRT
			$parent=WT_GedcomRecord::getInstance($pid);
			if ($factname == 'EVEN' || $factname=='FACT') {
				// Add ' EVEN' to provide sensible output for an event with an empty TYPE record
				$ct = preg_match("/2 TYPE (.*)/", $factrec, $ematch);
				if ($ct>0) {
					$factname = trim($ematch[1]);
					echo $factname;
				} else {
					echo WT_Gedcom_Tag::getLabel($factname, $parent);
				}
			} else if ($factname != 'NOTE') {
				// Note is already printed
				echo WT_Gedcom_Tag::getLabel($factname, $parent);
			}
		}
		echo '</td>';
		$nrec = get_sub_record($level, "$level NOTE", $factrec, $j+1);
		if (preg_match("/$level NOTE @(.*)@/", $match[$j][0], $nmatch)) {
			//-- print linked/shared note records
			$nid = $nmatch[1];
			$note=WT_Note::getInstance($nid);
			if ($note) {
				$text = $note->getNote();
				// If Census assistant installed,
				if ($fact->getTag()=='CENS' && array_key_exists('GEDFact_assistant', WT_Module::getActiveModules())) {
					$centitl  = str_replace('~~', '', $line1);
					$centitl  = str_replace('<br>', '', $centitl);
					$centitl  = "<a href=\"note.php?nid=$nid\">" . $centitl . '</a>';
					require WT_ROOT.WT_MODULES_DIR.'GEDFact_assistant/_CENS/census_note_decode.php';
				} else {
					$text = expand_urls($text);
				}
			} else {
				$text = '<span class="error">' . htmlspecialchars($nid) . '</span>';
			}
		} else {
			//-- print embedded note records
			$text = $match[$j][1] . get_cont($level+1, $nrec);
			$text = expand_urls($text);
		}

		echo '<td class="optionbox', $styleadd, ' wrap" align="', $TEXT_DIRECTION== "rtl"?"right": "left" , '">';
		echo '<div style="white-space:pre-wrap;">', $text, '</div>';

		if (!empty($noterec)) print_fact_sources($noterec, 1);

		// 2 RESN tags.  Note, there can be more than one, such as "privacy" and "locked"
		if (preg_match_all("/\n2 RESN (.+)/", $factrec, $matches)) {
			foreach ($matches[1] as $match) {
				echo '<br><span class="label">', WT_Gedcom_Tag::getLabel('RESN'), ':</span> <span class="field">';
				switch ($match) {
				case 'none':
					// Note: "2 RESN none" is not valid gedcom, and the GUI will not let you add it.
					// However, webtrees privacy rules will interpret it as "show an otherwise private fact to public".
					echo '<i class="icon-resn-none"></i> ', WT_I18N::translate('Show to visitors');
					break;
				case 'privacy':
					echo '<i class="icon-resn-privacy"></i> ', WT_I18N::translate('Show to members');
					break;
				case 'confidential':
					echo '<i class="icon-resn-confidential"></i> ', WT_I18N::translate('Show to managers');
					break;
				case 'locked':
					echo '<i class="icon-resn-locked"></i> ', WT_I18N::translate('Only managers can edit');
					break;
				default:
					echo $match;
					break;
				}
				echo '</span>';
			}
		}
		echo '<br>';
		print_fact_sources($nrec, $level+1);
		echo '</td></tr>';
	}
}

// Print a row for the media tab on the individual page
function print_main_media(WT_Fact $fact, $level) {
	$factrec = $fact->getGedcom();
	$fact_id = $fact->getFactId();
	$parent  = $fact->getParent();
	
	if ($fact->isNew()) {
		$styleadd = 'new';
		$can_edit = $level==1 && $fact->canEdit();
	} elseif ($fact->isOld()) {
		$styleadd='old';
		$can_edit = false;
	} else {
		$styleadd='';
		$can_edit = $level==1 && $fact->canEdit();
	}

	// -- find source for each fact
	preg_match_all('/(?:^|\n)' . $level . ' OBJE @(.*)@/', $factrec, $matches);
	foreach ($matches[1] as $xref) {
		$media=WT_Media::getInstance($xref);
		// Allow access to "1 OBJE @non_existent_source@", so it can be corrected/deleted
		if (!$media || $media->canShow()) {
			if ($level>1) {
				echo '<tr class="row_obje2">';
			} else {
				echo '<tr>';
			}
			echo '<td class="descriptionbox';
			if ($level>1) {
				echo ' rela';
			}
			echo ' ', $styleadd, ' width20">';
			$temp = preg_match("/^\d (\w*)/", $factrec, $factname);
			$factlines = explode("\n", $factrec); // 1 BIRT Y\n2 SOUR ...
			$factwords = explode(" ", $factlines[0]); // 1 BIRT Y
			$factname = $factwords[1]; // BIRT
			if ($factname == 'EVEN' || $factname=='FACT') {
				// Add ' EVEN' to provide sensible output for an event with an empty TYPE record
				$ct = preg_match("/2 TYPE (.*)/", $factrec, $ematch);
				if ($ct>0) {
					$factname = $ematch[1];
					echo $factname;
				} else {
					echo WT_Gedcom_Tag::getLabel($factname, $parent);
				}
			} else
			if ($can_edit) {
				echo '<a onclick="window.open(\'addmedia.php?action=editmedia&pid=', $media->getXref(), '\', \'_blank\', edit_window_specs); return false;" href="#" title="', WT_I18N::translate('Edit'), '">';
				echo WT_Gedcom_Tag::getLabel($factname, $parent), '</a>';
				echo '<div class="editfacts">';
				echo '<div class="editlink"><a class="editicon" onclick="window.open(\'addmedia.php?action=editmedia&pid=', $media->getXref(), '\', \'_blank\', edit_window_specs); return false;" href="#" title="', WT_I18N::translate('Edit'), '"><span class="link_text">', WT_I18N::translate('Edit'), '</span></a></div>';
				echo '<div class="copylink"><a class="copyicon" href="#" onclick="jQuery.post(\'action.php\',{action:\'copy-fact\', type:\'\', factgedcom:\''.rawurlencode($factrec).'\'},function(){location.reload();})" title="'.WT_I18N::translate('Copy').'"><span class="link_text">'.WT_I18N::translate('Copy').'</span></a></div>';
				echo '<div class="deletelink"><a class="deleteicon" onclick="return delete_fact(\'', WT_I18N::translate('Are you sure you want to delete this fact?'), '\', \'', $parent->getXref(), '\', \'', $fact->getFactId(), '\');" href="#" title="', WT_I18N::translate('Delete'), '"><span class="link_text">', WT_I18N::translate('Delete'), '</span></a></div>';
				echo '</div>';
			} else {
				echo WT_Gedcom_Tag::getLabel($factname, $parent);
			}
			echo '</td>';
			echo '<td class="optionbox ', $styleadd, ' wrap">';
			if ($media) {
				echo '<span class="field">';
				echo $media->displayImage();
				if (empty($SEARCH_SPIDER)) {
					echo '<a href="'.$media->getHtmlUrl().'">';
				}
				echo '<em>';
				foreach ($media->getAllNames() as $name) {
					if ($name['type']!='TITL') echo '<br>'; 
					echo $name['full'];
				}
				echo '</em>';
				if (empty($SEARCH_SPIDER)) {
					echo '</a>';
				}
			
				echo WT_Gedcom_Tag::getLabelValue('FORM', $media->mimeType());
				$imgsize = $media->getImageAttributes('main');
				if (!empty($imgsize['WxH'])) {
					echo WT_Gedcom_Tag::getLabelValue('__IMAGE_SIZE__', $imgsize['WxH']);
				}
				if ($media->getFilesizeraw()>0) {
					echo WT_Gedcom_Tag::getLabelValue('__FILE_SIZE__',  $media->getFilesize());
				}
				$mediatype=$media->getMediaType();
				if ($mediatype) {
					echo WT_Gedcom_Tag::getLabelValue('TYPE', WT_Gedcom_Tag::getFileFormTypeValue($mediatype));
				}
				echo '</span>';
				
				switch ($media->isPrimary()) {
				case 'Y':
					echo WT_Gedcom_Tag::getLabelValue('_PRIM', WT_I18N::translate('yes'));
					break;
				case 'N':
					echo WT_Gedcom_Tag::getLabelValue('_PRIM', WT_I18N::translate('no'));
					break;
				}
				print_fact_notes($media->getGedcom(), 1);
				print_fact_sources($media->getGedcom(), 1);
			} else {
				echo $xref;
			}
			echo '</td></tr>';
		}
	}	
}
