<?php
/***************************************************************************
 *   copyright				: (C) 2008 - 2014 WeBid
 *   site					: http://www.webidsupport.com/
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

define('InAdmin', 1);
$current_page = 'banners';
include '../common.php';
include $include_path . 'functions_admin.php';
include 'loggedin.inc.php';

if (!isset($_GET['banner']) || empty($_GET['banner']))
{
	header('location: managebanners.php');
	exit;
}

$banner = intval($_GET['banner']);
$query = "SELECT name, user FROM " . $DBPrefix . "banners WHERE id = :banner_id";
$params = array();
$params[] = array(':banner_id', $banner, 'int');
$db->query($query, $params);
$banner_ = $db->result();
$bannername = $banner_['name'];
$banneruser = $banner_['user'];

$query = "DELETE FROM " . $DBPrefix . "banners WHERE id = :banner_id";
$db->query($query, $params);
$query = "DELETE FROM " . $DBPrefix . "bannerscategories WHERE banner = :banner_id";
$db->query($query, $params);
$query = "DELETE FROM " . $DBPrefix . "bannerskeywords WHERE banner = :banner_id";
$db->query($query, $params);
@unlink($upload_path . 'banners/' . $banneruser . '/' . $bannername);

// Redirect
header('location: userbanners.php?id=' . $banneruser);
?>
