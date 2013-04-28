<?

/**
 * 
 * This file copyright (C) 2006 Barry Hunter (mgrs@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

class MGRS {

	var $northingIDs = array ( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V');
	
	function fromUTM($utm) {

		$lngZone = $utm->lngZone;
		$set = (($lngZone - 1) % 6) + 1;
	
		$eID = floor($utm->easting / 100000.0) + (8 * (($set - 1) % 3)) ;
		$nID = floor(($utm->northing % 2000000.0) / 100000.0);

		if ($eID > 8)
		  $eID++; // Offset for no I character
		if ($eID > 14)
		  $eID++; // Offset for no O character

		$eIDc = chr($eID + 64);

		// Northing ID offset for sets 2, 4 and 6
		if ($set % 2 == 0) {
		  $nID += 5;
		}

		if ($nID > 19) {
		  $nID -= 20;
		}

		$nIDc = $this->northingIDs[$nID];

		$this->utmZoneNumber = $lngZone;
		$this->utmZoneChar = $utm->latZone;
		$this->eastingID = $eIDc;
		$this->northingID = $nIDc;
		$this->easting = round($utm->easting) % 100000;
		$this->northing = round($utm->northing) % 100000;
		$this->precision = 1;
	}


	function toString($precision) {

		if ($precision != 1 && precision != 10
			&& precision != 100 && precision != 1000
			&& precision != 10000) {
			die("Precision (" + precision
			  + ") must be 1m, 10m, 100m, 1000m or 10000m");
		}

		$eastingR = floor($this->easting / $precision);
		$northingR = floor($this->northing / $precision);

		$padding = 5;

		switch ($precision) {
			case 10:	$padding = 4;	break;
			case 100:	$padding = 3;	break;
			case 1000:	$padding = 2;	break;
			case 10000:	$padding = 1;	break;
		}

		$ez = $padding - strlen($eastingR);
		while ($ez > 0) {
		  $eastingR = '0' . $eastingR;
		  $ez--;
		}

		$nz = $padding - strlen($northingR);
		while ($nz > 0) {
		  $northingR = '0' . $northingR;
		  $nz--;
		}

		$utmZonePadding = '';
		if ($this->utmZoneNumber < 10) {
		  $utmZonePadding = '0';
		}

		return $utmZonePadding . $this->utmZoneNumber . $this->utmZoneChar . ' ' . $this->eastingID . $this->northingID . ' ' . $eastingR . ' ' . $northingR;
	}

}

?>