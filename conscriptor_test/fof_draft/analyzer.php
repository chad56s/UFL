<?
/***************************************************************************
 *                                analizer.php
 *                            -------------------
 *   begin                : Saturday, May 24, 2008
 *   copyright            : (C) 2008 J. David Baker
 *   email                : me@jdavidbaker.com
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

include "includes/classes.inc.php";
$statement = "select * from pick, player, position where
pick.player_id = player.player_id and
position.position_id = player.position_id
order by pick_id";
$result = mysql_query($statement);
echo mysql_error();
$line = array();
while ($row = mysql_fetch_array($result)) {
  list($first, $last) = explode(" ",$row['player_name']);
  $line[] = $row['pick_id'].'. - '.$last.', '.$first.', '.$row['position_name'].', '.$row['player_school'];
}
if (count($line)) {
  echo implode("<br>",$line);
} else {
  echo "No drafted players";
}
?>