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

if ($_GET['BBOX']) {
	$coords = preg_split('/,|\s/', str_replace('e ','e+',$_GET['BBOX']));
		
	// calculate the approx center of the view -- note that this is innaccurate if the user is not looking straight down
	$long = (($coords[2] - $coords[0])/2) + $coords[0];
	$lat = (($coords[3] - $coords[1])/2) + $coords[1];

	$span = min($coords[3] - $coords[1],$coords[2] - $coords[0]);



} elseif ($_GET['lat'] && $_GET['long']) {
	$lat = $_GET['lat'];
	$long = $_GET['long'];
	$span = 1;
} else {
	//may as well zoom go somwhere (central UK)
	$lat = 52.743712;
	$long = -3.885721;
}

if ($_GET['LOOKAT']) {
	$looks = preg_split('/,|\s/', str_replace('e ','e+',$_GET['LOOKAT']));
	$lat = $looks[1];
	$long = $looks[0];
}

###############################



if ($span > 8 || !$span) {
		
	
print "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
?>
<kml xmlns="http://earth.google.com/kml/2.0">
<Document>
<Folder>
	<name>Please zoom in to plot GridLines</name>
	<visibility>1</visibility>
</Folder>
</Document>
</kml>
<?
	
	exit;
}

require("phpcoord-2.1.php");
require("phpcoord-mgrs.php");

################################
# personal fuinctions to forse the use of a zone!

 $latZone = '';
 $lngZone = '';

function latlong_to_utm($lat,$long) {
	global $latZone,$lngZone;
	$ll4 = new LatLng($lat,$long);
	$utm2 = $ll4->toUTMRef($latZone, $lngZone);	
	
	return array($utm2->easting,$utm2->northing);
}

function utm_to_latlong($e,$n) {
	global $latZone,$lngZone;
	$utm1 = new UTMRef($e, $n, $latZone, $lngZone);
	$ll3 = $utm1->toLatLng();
	return array($ll3->lat,$ll3->lng);
}

# # # # # # # # # # 

		$ll4 = new LatLng($lat,$long);
		$utm2 = $ll4->toUTMRef();

		$latZone = $utm2->latZone;
		$lngZone = $utm2->lngZone;
    
	list ($ce,$cn) = array($utm2->easting,$utm2->northing);
	$utm_gridref = $utm2->toString(); //for the nearby.org.uk link
	
	$mgrs = new MGRS();
	$mgrs->fromUTM($utm2);
	$gridref = $mgrs->toString(1);
	
	
	
################################

#BBOX=[bboxWest],[bboxSouth],[bboxEast],[bboxNorth]
#BBOX=-3,52,-2,53

#long - (west / east)
#lat - (south / north)

#$coords[0] - west;
#$coords[1] - south;
#$coords[2] - east;
#$coords[3] - north;

//todo if LOOKAT is very off center then can try reducing the grid

#south
list (,$n1) = latlong_to_utm($coords[1],$long);
#north
list (,$n2) = latlong_to_utm($coords[3],$long);

#west
list ($e1,) = latlong_to_utm($lat,$coords[0]);
#east
list ($e2,) = latlong_to_utm($lat,$coords[2]);

################################

$ow1 = $ow = $e2 - $e1;

if ($ow < 2500) {

	$prec = ($ow > 250)?10:100;

	$e1 *=$prec; $e2 *=$prec; $n1 *=$prec; $n2 *=$prec; 

	if ($e1 == $e2 && $n1 == $n1) {
		$e2 = $e1 = floor($e1 / 1000) * 1000;
		$n2 = $n1 = floor($n1 / 1000) * 1000;
	} else {
		$e1 = floor($e1 / 1000) * 1000;
		$e2 =  ceil($e2 / 1000) * 1000; 

		$n1 = floor($n1 / 1000) * 1000;
		$n2 =  ceil($n2 / 1000) * 1000;
	}
	
	$e1 /=$prec; $e2 /=$prec; $n1 /=$prec; $n2 /=$prec; 
	
} else {

	if ($_GET['b'] > 0) {
		$enlarge = round($_GET['b'])*1000;#+100;
	} else {
		$enlarge = 0;#100;
	}

	if ($_GET['b'] != -1) {
		if ($e1 == $e2 && $n1 == $n2) {
			$e2 = $e1 = floor($e1 / 1000) * 1000;
			$n2 = $n1 = floor($n1 / 1000) * 1000;
		} else {
			$e1 = floor($e1 / 1000) * 1000;
			$e2 =  ceil($e2 / 1000) * 1000; 

			$n1 = floor($n1 / 1000) * 1000;
			$n2 =  ceil($n2 / 1000) * 1000;
		}
	}

	$e1 -= $enlarge;
	$e2 += $enlarge;

	$n1 -= $enlarge;
	$n2 += $enlarge;

}

################################

$ow = $e2 - $e1;
$oh = $n2 - $n1;

if ($ow > $oh) {
	$bot = floor(($ow - $oh)/2000) * 1000;
	$top = ($ow - $oh) - $bot;
	$n1 -= $bot;
	$n2 += $top;
} else if ($oh > $ow) {
	$bot = floor(($oh - $ow)/2000) * 1000;
	$top = ($oh - $ow) - $bot;
	$e1 -= $bot;
	$e2 += $top;
}

###########################

	$ow = $e2 - $e1;

	if ($ow > 250000) {
		$mod = 100000;
	} else if ($ow > 25000) {
		$mod = 10000;
	} else if ($ow > 2500) {
		$mod = 1000;
	} else if ($ow > 250) {
		$mod = 100;
	} else {
		$mod = 10;
	}

	$lines = array();
	$lines2 = array();
	$points = array();

	if ($mod < 1000) {
######################################
		$nm = floor( ($n2+$n1)/2);
		$ll = '';
		
		$prec = ($mod == 100)?1:2;
		$padding = ($mod == 100)?6:7;

		for ($ee = $e1;$ee <= $e2;$ee+=10) {
			if ($ee%$mod == 0) {
				$line = array();
				list($line['sLat'],$line['sLong']) = utm_to_latlong($ee,$n1);
				list($line['eLat'],$line['eLong']) = utm_to_latlong($ee,$n2);
				if (($mod == 10 && $ee%100 == 0) || ($mod == 100 && $ee%1000 == 0)) {
					$lines[] = $line;
				} else {
					$lines2[] = $line;
				}

				#if ($mod < 100000) {
					$point = array();
					list($point['Lat'],$point['Long']) = utm_to_latlong($ee,$cn);
					$point['Label'] = substr(sprintf("%{$padding}.{$prec}f",($ee/1000)),2);
					$points[] = $point;
				#}
			}
		}

		$em = floor( ($e2+$e1)/2);
		$ll = '';

		for ($nn = $n1;$nn <= $n2;$nn+=10) {
			if ($nn%$mod == 0) {
				$line = array();
				list($line['sLat'],$line['sLong']) = utm_to_latlong($e1,$nn);
				list($line['eLat'],$line['eLong']) = utm_to_latlong($e2,$nn);
				if (($mod == 10 && $nn%100 == 0) || ($mod == 100 && $nn%1000 == 0)) {
					$lines[] = $line;
				} else {
					$lines2[] = $line;
				}

				#if ($mod < 100000) {
					$point = array();
					list($point['Lat'],$point['Long']) = utm_to_latlong($ce,$nn);
					$point['Label'] = substr(sprintf("%{$padding}.{$prec}f",($nn/1000)),2);
					$points[] = $point;
				#}
			}
		}

		$label1 = sprintf("%.{$prec}f",$mod/100);
		$label2 = sprintf("%.{$prec}f",$mod/1000);
	
		if (count($lines2) && !count($lines)) {
			$lines =$lines2;
			$lines2 = array();
			$label1 =  sprintf("%.{$prec}f",$mod/1000);
		}
######################################	
	} else {

		$nm = floor( ($n2+$n1)/2);
		$ll = '';

		for ($ee = $e1;$ee <= $e2;$ee+=1000) {
			if ($ee%$mod == 0) {
				$line = array();
				list($line['sLat'],$line['sLong']) = utm_to_latlong($ee,$n1);
				list($line['eLat'],$line['eLong']) = utm_to_latlong($ee,$n2);
				if (($mod == 1000 && $ee%10000 == 0) || ($mod == 10000 && $ee%100000 == 0)) {
					$lines[] = $line;
				} else {
					$lines2[] = $line;
				}

				#if ($mod < 100000) {
					$point = array();
					list($point['Lat'],$point['Long']) = utm_to_latlong($ee,$cn);
					$point['Label'] = intval($ee/1000)%100;
					$points[] = $point;
				#}
			}
		}

		$em = floor( ($e2+$e1)/2);
		$ll = '';

		for ($nn = $n1;$nn <= $n2;$nn+=1000) {
			if ($nn%$mod == 0) {
				$line = array();
				list($line['sLat'],$line['sLong']) = utm_to_latlong($e1,$nn);
				list($line['eLat'],$line['eLong']) = utm_to_latlong($e2,$nn);
				if (($mod == 1000 && $nn%10000 == 0) || ($mod == 10000 && $nn%100000 == 0)) {
					$lines[] = $line;
				} else {
					$lines2[] = $line;
				}

				#if ($mod < 100000) {
					$point = array();
					list($point['Lat'],$point['Long']) = utm_to_latlong($ce,$nn);
					$point['Label'] = intval($nn/1000)%100;
					$points[] = $point;
				#}
			}
		}

		$label1 = floor($mod/100);
		$label2 = floor($mod/1000);
	
		if (count($lines2) && !count($lines)) {
			$lines =$lines2;
			$lines2 = array();
			$label1 = floor($mod/1000);
		}
	}


###########################

ob_start();
	print "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
?>
<kml xmlns="http://earth.google.com/kml/2.0">
<Document>
<Style id="lineStyle">
	<LineStyle>
		<color>ff00ffff</color>
		<width>2</width>
	</LineStyle>
</Style>
<Style id="lineStyle2">
	<LineStyle>
		<color>7f00ff00</color>
		<width>1</width>
	</LineStyle>
</Style>
<Style id="labelStyle">
	<IconStyle>
		<scale>0</scale>
	</IconStyle>
</Style>
<Placemark>
<name><? echo $label1; ?>km MGRS/UTM Grid Lines</name>
<visibility>1</visibility>
<styleUrl>#lineStyle</styleUrl>
<MultiGeometry>
<?
	
	foreach ($lines as $line) {
?>
  <LineString>
  	<altitudeMode>clampedToGround</altitudeMode>
  	<tessellate>1</tessellate>
    <coordinates>
        <? echo "{$line['sLong']},{$line['sLat']},25
        {$line['eLong']},{$line['eLat']},25
        "; ?>
    </coordinates>
  </LineString>
<?
	}
	
if (count($lines2)) {
?>
</MultiGeometry>
</Placemark>
<Placemark>
<name><? echo $label2; ?>km Grid Lines</name>
<visibility>1</visibility>
<styleUrl>#lineStyle2</styleUrl>
<MultiGeometry>
<?
	foreach ($lines2 as $line) {
?>
  <LineString>
  	<altitudeMode>clampedToGround</altitudeMode>
  	<tessellate>1</tessellate>
    <coordinates>
        <? echo "{$line['sLong']},{$line['sLat']},25
        {$line['eLong']},{$line['eLat']},25
        "; ?>
    </coordinates>
  </LineString>
<?
	}
}
	
?>
</MultiGeometry>
</Placemark>
<Folder>
<name>Labels</name>
<visibility>1</visibility>

<?
	foreach ($points as $point) {
?>
  <Placemark>
  	<name><? echo $point['Label']; ?></name>
    <visibility>1</visibility>	
	<Point>
		<coordinates><? echo $point['Long']; ?>,<? echo $point['Lat']; ?>,25</coordinates>
	</Point>
	<styleUrl>#labelStyle</styleUrl>
  </Placemark>
<?
	}
?>
</Folder>
<Placemark>
	<name><? echo $gridref ?></name>
	<description><![CDATA[
			Center Point<br />
			<a href="http://www.nearby.org.uk/coord.cgi?p=<? echo urlencode($utm_gridref); ?>">View at Nearby.org.uk</a>
		]]></description>
	<visibility>0</visibility>
	<Point>
		<coordinates><? echo $long; ?>,<? echo $lat; ?>,25</coordinates>
	</Point>
	<Style>
		<IconStyle>
			<Icon>
				<href>root://icons/palette-3.png</href> 
				<y>64</y> 
				<w>32</w> 
				<h>32</h> 
			</Icon>
		</IconStyle>
	</Style>
</Placemark>
</Document></kml><?

$filedata = ob_get_contents();
ob_end_clean();

if ($_GET['d']) {
	#$filedate = str_replace('<','&lt',$filedata);
	#$filedate = str_replace('>','&lt',$filedata);
	header("Content-Type: text/xml");
	print $filedata; exit;
}

include("zip.class.php");

$zipfile = new zipfile();   

// add the binary data stored in the string 'filedata' 
$zipfile -> addFile($filedata, "doc.kml");   

// the next three lines force an immediate download of the zip file: 
# let the browser know what's coming
header("Content-type: application/vnd.google-earth.kmz");
header("Content-Disposition: attachment; filename=\"grid.nearby.kmz\"");

echo $zipfile -> file();   



?>