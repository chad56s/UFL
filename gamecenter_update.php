<?php
/***************************************************************************
 *                                gamecenter_update.inc.php
 *                            ---------------------------------
 *   begin                : Monday, October 20, 2008
 *   copyright            : (C) 2008 J. David Baker
 *   email                : me@jdavidbaker.com
 *
 *   $Id: gamecenter_update.php,v 1.1 2008/10/21 12:26:29 jonbaker Exp $
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

include_once("config.php");
include_once(OBJECT_ROOT . "/GameCenter.php");
$gamecenter = new GameCenter($_GET['game_id'],$_GET['last_id']);
echo $gamecenter->update();
?>
