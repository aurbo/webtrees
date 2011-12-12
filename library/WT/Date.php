<?php
// Classes for Gedcom Date/Calendar functionality.
//
// WT_Date represents the date or date range from a gedcom DATE record.
//
// NOTE: Since different calendars start their days at different times, (civil
// midnight, solar midnight, sunset, sunrise, etc.), we convert on the basis of
// midday.
//
// NOTE: We assume that years start on the first day of the first month.  Where
// this is not the case (e.g. England prior to 1752), we need to use modified
// years or the OS/NS notation "4 FEB 1750/51".
//
// NOTE: WT should only be using the WT_Date class.  The other classes
// are all for internal use only.
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
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
// @author Greg Roach
// @version $Id$

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class WT_Date {
	var $qual1=null; // Optional qualifier, such as BEF, FROM, ABT
	var $date1=null; // The first (or only) date
	var $qual2=null; // Optional qualifier, such as TO, AND
	var $date2=null; // Optional second date
	var $text =null; // Optional text, as included with an INTerpreted date

	function __construct($date) {
		// Extract any explanatory text
		if (preg_match('/^(.*) ?[(](.*)[)]/', $date, $match)) {
			$date=$match[1];
			$this->text=$match[2];
		}
		// Ignore punctuation and normalise whitespace
		$date=preg_replace(
			array('/(\d+|@#[^@]+@)/', '/[\s;:.,-]+/', '/^ /', '/ $/'),
			array(' $1 ', ' ', '', ''),
			strtolower($date)
		);
		if (preg_match('/^(from|bet) (.+) (and|to) (.+)/', $date, $match)) {
			$this->qual1=$match[1];
			$this->date1=$this->ParseDate($match[2]);
			$this->qual2=$match[3];
			$this->date2=$this->ParseDate($match[4]);
		} elseif (preg_match('/^(from|bet|to|and|bef|aft|cal|est|int|abt) (.+)/', $date, $match)) {
			$this->qual1=$match[1];
			$this->date1=$this->ParseDate($match[2]);
		} else {
			$this->date1=$this->ParseDate($date);
		}
	}

	// Need to "deep-clone" nested objects
	function __clone() {
		$this->date1=clone $this->date1;
		if (is_object($this->date2)) {
			$this->date2=clone $this->date2;
		}
	}

	// Convert an individual gedcom date string into a WT_Date_Calendar object
	static function ParseDate($date) {
		// Valid calendar escape specified? - use it
		if (preg_match('/^(@#d(?:gregorian|julian|hebrew|hijri|jalali|french r|roman|jalali)+@) ?(.*)/', $date, $match)) {
			$cal=$match[1];
			$date=$match[2];
		} else {
			$cal='';
		}
		// A date with a month: DM, M, MY or DMY
		if (preg_match('/^(\d?\d?) ?(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|tsh|csh|ksl|tvt|shv|adr|ads|nsn|iyr|svn|tmz|aav|ell|vend|brum|frim|nivo|pluv|vent|germ|flor|prai|mess|ther|fruc|comp|muhar|safar|rabi[at]|juma[at]|rajab|shaab|ramad|shaww|dhuaq|dhuah|farva|ordib|khord|tir|morda|shahr|mehr|aban|azar|dey|bahma|esfan) ?((?:\d+(?: b ?c)?|\d\d\d\d \/ \d{1,4})?)$/', $date, $match)) {
			$d=$match[1];
			$m=$match[2];
			$y=$match[3];
		} else
			// A date with just a year
			if (preg_match('/^(\d+(?: b ?c)?|\d\d\d\d \/ \d{1,4})$/', $date, $match)) {
				$d='';
				$m='';
				$y=$match[1];
			} else {
				// An invalid date - do the best we can.
				$d='';
				$m='';
				$y='';
				// Look for a 3/4 digit year anywhere in the date
				if (preg_match('/\b(\d{3,4})\b/', $date, $match)) {
					$y=$match[1];
				}
				// Look for a month anywhere in the date
				if (preg_match('/(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|tsh|csh|ksl|tvt|shv|adr|ads|nsn|iyr|svn|tmz|aav|ell|vend|brum|frim|nivo|pluv|vent|germ|flor|prai|mess|ther|fruc|comp|muhar|safar|rabi[at]|juma[at]|rajab|shaab|ramad|shaww|dhuaq|dhuah|farva|ordib|khord|tir|morda|shahr|mehr|aban|azar|dey|bahma|esfan)/', $date, $match)) {
					$m=$match[1];
					// Look for a day number anywhere in the date
					if (preg_match('/\b(\d\d?)\b/', $date, $match))
						$d=$match[1];
				}
			}
		// Unambiguous dates - override calendar escape
		if (preg_match('/^(tsh|csh|ksl|tvt|shv|adr|ads|nsn|iyr|svn|tmz|aav|ell)$/', $m)) {
			$cal='@#dhebrew@';
		} else {
			if (preg_match('/^(vend|brum|frim|nivo|pluv|vent|germ|flor|prai|mess|ther|fruc|comp)$/', $m)) {
				$cal='@#dfrench r@';
			} else {
				if (preg_match('/^(muhar|safar|rabi[at]|juma[at]|rajab|shaab|ramad|shaww|dhuaq|dhuah)$/', $m)) {
					$cal='@#dhijri@'; // This is a WT extension
				} else {
					if (preg_match('/^(farva|ordib|khord|tir|morda|shahr|mehr|aban|azar|dey|bahma|esfan)$/', $m)) {
						$cal='@#djalali@'; // This is a WT extension 
					} elseif (preg_match('/^\d+( b ?c)|\d\d\d\d \/ \d{1,4}$/', $y)) {
						$cal='@#djulian@';
					}

				}
			}
		}
		// Ambiguous dates - don't override calendar escape
		if ($cal=='') {
			if (preg_match('/^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)$/', $m)) {
				$cal='@#dgregorian@';
			} else {
				if (preg_match('/^[345]\d\d\d$/', $y)) { // Year 3000-5999
					$cal='@#dhebrew@';
				} else {
					$cal='@#dgregorian@';
				}
			}
		}
		// Now construct an object of the correct type
		switch ($cal) {
		case '@#dgregorian@':
			return new WT_Date_Gregorian(array($y, $m, $d));
		case '@#djulian@':
			return new WT_Date_Julian(array($y, $m, $d));
		case '@#dhebrew@':
			return new WT_Date_Jewish(array($y, $m, $d));
		case '@#dhijri@':
			return new WT_Date_Hijri(array($y, $m, $d));
		case '@#dfrench r@':
			return new WT_Date_French(array($y, $m, $d));
		case '@#djalali@':
			return new WT_Date_Jalali(array($y, $m, $d));
		case '@#droman@':
			return new WT_Date_Roman(array($y, $m, $d));
		}
	}

	// Convert a date to the prefered format and calendar(s) display.
	// Optionally make the date a URL to the calendar.
	function Display($url=false, $date_fmt=null, $cal_fmts=null) {
		global $TEXT_DIRECTION, $DATE_FORMAT, $CALENDAR_FORMAT;

		// Convert dates to given calendars and given formats
		if (!$date_fmt) {
			$date_fmt=$DATE_FORMAT;
		}
		if (is_null($cal_fmts))
			$cal_fmts=explode('_and_', $CALENDAR_FORMAT);

		// Two dates with text before, between and after
		$q1=$this->qual1;
		$d1=$this->date1->Format($date_fmt, $this->qual1);
		$q2=$this->qual2;
		if (is_null($this->date2))
			$d2='';
		else
			$d2=$this->date2->Format($date_fmt, $this->qual2);
		// Convert to other calendars, if requested
		$conv1='';
		$conv2='';
		foreach ($cal_fmts as $cal_fmt)
			if ($cal_fmt!='none') {
				$d1conv=$this->date1->convert_to_cal($cal_fmt);
				if ($d1conv->InValidRange()) {
					$d1tmp=$d1conv->Format($date_fmt, $this->qual1);
				} else {
					$d1tmp='';
				}
				$q1tmp=$this->qual1;
				if (is_null($this->date2)) {
					$d2conv=null;
					$d2tmp='';
				} else {
					$d2conv=$this->date2->convert_to_cal($cal_fmt);
					if ($d2conv->InValidRange()) {
						$d2tmp=$d2conv->Format($date_fmt, $this->qual2);
					} else {
						$d2tmp='';
					}
				}
				$q2tmp=$this->qual2;
				// If the date is different to the unconverted date, add it to the date string.
				if ($d1!=$d1tmp && $d1tmp!='') {
					if ($url) {
						if ($CALENDAR_FORMAT!="none") {
							$conv1.=' <span dir="'.$TEXT_DIRECTION.'">(<a href="'.$d1conv->CalendarURL($date_fmt).'">'.$d1tmp.'</a>)</span>';
						} else {
							$conv1.=' <span dir="'.$TEXT_DIRECTION.'"><br><a href="'.$d1conv->CalendarURL($date_fmt).'">'.$d1tmp.'</a></span>';
						}
					} else {
						$conv1.=' <span dir="'.$TEXT_DIRECTION.'">('.$d1tmp.')</span>';
					}
				}
				if (!is_null($this->date2) && $d2!=$d2tmp && $d1tmp!='') {
					if ($url) {
						$conv2.=' <span dir="'.$TEXT_DIRECTION.'">(<a href="'.$d2conv->CalendarURL($date_fmt).'">'.$d2tmp.'</a>)</span>';
					} else {
						$conv2.=' <span dir="'.$TEXT_DIRECTION.'">('.$d2tmp.')</span>';
					}
				}
			}

		// Add URLs, if requested
		if ($url) {
			$d1='<a href="'.$this->date1->CalendarURL($date_fmt).'">'.$d1.'</a>';
			if (!is_null($this->date2))
				$d2='<a href="'.$this->date2->CalendarURL($date_fmt).'">'.$d2.'</a>';
		}

		// Localise the date
		switch ($q1.$q2) {
		case '':       $tmp=$d1.$conv1; break;
		case 'abt':    /* I18N: Gedcom ABT dates     */ $tmp=WT_I18N::translate('about %s',            $d1.$conv1); break;
		case 'cal':    /* I18N: Gedcom CAL dates     */ $tmp=WT_I18N::translate('calculated %s',       $d1.$conv1); break;
		case 'est':    /* I18N: Gedcom EST dates     */ $tmp=WT_I18N::translate('estimated %s',        $d1.$conv1); break;
		case 'int':    /* I18N: Gedcom INT dates     */ $tmp=WT_I18N::translate('interpreted %s (%s)', $d1.$conv1, $this->text); break;
		case 'bef':    /* I18N: Gedcom BEF dates     */ $tmp=WT_I18N::translate('before %s',           $d1.$conv1); break;
		case 'aft':    /* I18N: Gedcom AFT dates     */ $tmp=WT_I18N::translate('after %s',            $d1.$conv1); break;
		case 'from':   /* I18N: Gedcom FROM dates    */ $tmp=WT_I18N::translate('from %s',             $d1.$conv1); break;
		case 'to':     /* I18N: Gedcom TO dates      */ $tmp=WT_I18N::translate('to %s',               $d1.$conv1); break;
		case 'betand': /* I18N: Gedcom BET-AND dates */ $tmp=WT_I18N::translate('between %s and %s',   $d1.$conv1, $d2.$conv2); break;
		case 'fromto': /* I18N: Gedcom FROM-TO dates */ $tmp=WT_I18N::translate('from %s to %s',       $d1.$conv1, $d2.$conv2); break;
		default: $tmp=WT_I18N::translate('Invalid date'); break; // e.g. BET without AND
		}

		// Return at least one printable character, for better formatting in tables.
		if (strip_tags($tmp)=='')
			return '&nbsp;';
		else
			return "<span class=\"date\">{$tmp}</span>";
	}

	// Get the earliest/latest date/JD from this date
	function MinDate() {
		return $this->date1;
	}
	function MaxDate() {
		if (is_null($this->date2))
			return $this->date1;
		else
			return $this->date2;
	}
	function MinJD() {
		$tmp=$this->MinDate();
		return $tmp->minJD;
	}
	function MaxJD() {
		$tmp=$this->MaxDate();
		return $tmp->maxJD;
	}
	function JD() {
		return floor(($this->MinJD()+$this->MaxJD())/2);
	}

	// Offset this date by N years, and round to the whole year
	function AddYears($n, $qual='') {
		$tmp=clone $this;
		$tmp->date1->y+=$n;
		$tmp->date1->m=0;
		$tmp->date1->d=0;
		$tmp->date1->SetJDfromYMD();
		$tmp->qual1=$qual;
		$tmp->qual2='';
		$tmp->date2=null;
		return $tmp;
	}

	// Calculate the number of full years between two events.
	// Return the result as either a number of years (for indi lists, etc.)
	static function GetAgeYears($d1, $d2=null, $warn_on_negative=true) {
		if (!is_object($d1)) {
			return '';
		} elseif (!is_object($d2)) {
			return $d1->date1->GetAge(false, WT_CLIENT_JD, $warn_on_negative );
		} else {
			return $d1->date1->GetAge(false, $d2->MinJD(), $warn_on_negative);
		}
	}

	// Calculate the years/months/days between two events
	// Return a gedcom style age string: "1y 2m 3d" (for fact details)
	static function GetAgeGedcom($d1, $d2=null, $warn_on_negative=true) {
		if (is_null($d2)) {
			return $d1->date1->GetAge(true, WT_CLIENT_JD, $warn_on_negative);
		} else {
			// If dates overlap, then can't calculate age.
			if (WT_Date::Compare($d1, $d2)) {
				return $d1->date1->GetAge(true, $d2->MinJD(), $warn_on_negative);
			} if (WT_Date::Compare($d1, $d2)==0 && $d1->date1->minJD==$d2->MinJD()) {
				return '0d';
			} else {
				return '';
			}
		}
	}

	// Static function to compare two dates.
	// return <0 if $a<$b
	// return >0 if $b>$a
	// return  0 if dates same/overlap
	// BEF/AFT sort as the day before/after.
	static function Compare($a, $b) {
		// Get min/max JD for each date.
		switch ($a->qual1) {
		case 'bef':
			$amin=$a->MinJD()-1;
			$amax=$amin;
			break;
		case 'aft':
			$amax=$a->MaxJD()+1;
			$amin=$amax;
			break;
		default:
			$amin=$a->MinJD();
			$amax=$a->MaxJD();
			break;
		}
		switch ($b->qual1) {
		case 'bef':
			$bmin=$b->MinJD()-1;
			$bmax=$bmin;
			break;
		case 'aft':
			$bmax=$b->MaxJD()+1;
			$bmin=$bmax;
			break;
		default:
			$bmin=$b->MinJD();
			$bmax=$b->MaxJD();
			break;
		}
		if ($amax<$bmin) {
			return -1;
		} else {
			if ($amin>$bmax && $bmax>0) {
				return 1;
			} else {
				if ($amin<$bmin && $amax<=$bmax) {
					return -1;
				} elseif ($amin>$bmin && $amax>=$bmax && $bmax>0) {
					return 1;
				} else {
					return 0;
				}
			}
		}
	}

	// Check whether a gedcom date contains usable calendar date(s).
	function isOK() {
		return $this->MinJD() && $this->MaxJD();
	}

	// Calculate the gregorian year for a date.  This should NOT be used internally
	// within WT - we should keep the code "calendar neutral" to allow support for
	// jewish/arabic users.  This is only for interfacing with external entities,
	// such as the ancestry.com search interface or the dated fact icons.
	function gregorianYear() {
		if ($this->isOK()) {
			list($y)=WT_Date_Gregorian::JDtoYMD($this->JD());
			return $y;
		} else {
			return 0;
		}
	}
}
