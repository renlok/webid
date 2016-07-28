<?php
/***************************************************************************
 *   copyright				: (C) 2008 - 2016 WeBid
 *   site					: http://www.webidsupport.com/
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

include 'common.php';

if (isset($_GET['user_id']) && !empty($_GET['user_id']))
{
	$user_id = intval($_GET['user_id']);
}
elseif ($user->logged_in)
{
	$user_id = $user->user_data['id'];
}
else
{
	$_SESSION['LOGIN_MESSAGE'] = $MSG['5000'];
	$_SESSION['REDIRECT_AFTER_LOGIN'] = 'active_auctions.php';
	header('location: user_login.php');
	exit;
}

// check trying to access valid user id
$user->is_valid_user($user_id);

$NOW = time();

// get number of active auctions for this user
$query = "SELECT count(id) AS auctions FROM " . $DBPrefix . "auctions
		WHERE user = :user_id
		AND closed = 0
		AND starts <= :time";
$params = array();
$params[] = array(':user_id', $user_id, 'int');
$params[] = array(':time', $NOW, 'int');
$db->query($query, $params);
$num_auctions = $db->result('auctions');

// Handle pagination
if (!isset($_GET['PAGE']) || $_GET['PAGE'] == '' || $_GET['PAGE'] < 1)
{
	$OFFSET = 0;
	$PAGE = 1;
}
else
{
	$PAGE = intval($_GET['PAGE']);
	$OFFSET = ($PAGE - 1) * $system->SETTINGS['perpage'];
}
$PAGES = ceil($num_auctions / $system->SETTINGS['perpage']);
if (!isset($PAGES) || $PAGES < 1) $PAGES = 1;

$query = "SELECT * FROM " . $DBPrefix . "auctions
		WHERE user = :user_id
		AND closed = 0
		AND starts <= :time
		ORDER BY ends ASC LIMIT :offset, :perpage";
$params = array();
$params[] = array(':user_id', $user_id, 'int');
$params[] = array(':time', $NOW, 'int');
$params[] = array(':offset', $OFFSET, 'int');
$params[] = array(':perpage', $system->SETTINGS['perpage'], 'int');
$db->query($query, $params);

$k = 0;
while ($row = $db->fetch())
{
	if (strlen($row['pict_url']) > 0)
	{
		$row['pict_url'] = $system->SETTINGS['siteurl'] . 'getthumb.php?w=' . $system->SETTINGS['thumb_show'] . '&fromfile=' . UPLOAD_FOLDER . $row['id'] . '/' . $row['pict_url'];
	}
	else
	{
		$row['pict_url'] = get_lang_img('nopicture.gif');
	}

	// number of bids for this auction
	$query = "SELECT bid FROM " . $DBPrefix . "bids WHERE auction = :id";
	$params[] = array(':id', $row['id'], 'int');
	$db->query($query, $params);
	$num_bids = $db->numrows();

	$difference = $row['ends'] - $NOW;

	$template->assign_block_vars('auctions', array(
			'BGCOLOUR' => (!($k % 2)) ? '' : 'class="alt-row"',
			'ID' => $row['id'],
			'PIC_URL' => $row['pict_url'],
			'TITLE' => $system->uncleanvars($row['title']),
			'BNIMG' => get_lang_img(($row['bn_only'] == 0) ? 'buy_it_now.gif' : 'bn_only.png'),
			'BNVALUE' => $row['buy_now'],
			'BNFORMAT' => $system->print_money($row['buy_now']),
			'BIDVALUE' => $row['current_bid'],
			'BIDFORMAT' => $system->print_money($row['current_bid']),
			'NUM_BIDS' => $num_bids,
			'TIMELEFT' => FormatTimeLeft($difference),

			'B_BUY_NOW' => ($row['buy_now'] > 0 && ($row['bn_only'] || $row['bn_only'] == 0 && ($row['num_bids'] == 0 || ($row['reserve_price'] > 0 && $row['current_bid'] < $row['reserve_price'])))),
			'B_BNONLY' => ($row['bn_only'])
			));
	$k++;
}

// get this user's nick
$query = "SELECT nick FROM " . $DBPrefix . "users WHERE id = :user_id";
$params[] = array(':user_id', $user_id, 'int');
$db->query($query, $params);
$TPL_user_nick = $db->result('nick');
$page_title = $MSG['219'] . ': ' . $TPL_user_nick;

$LOW = $PAGE - 5;
if ($LOW <= 0) $LOW = 1;
$COUNTER = $LOW;
$pagenation = '';
while ($COUNTER <= $PAGES && $COUNTER < ($PAGE + 6))
{
	if ($PAGE == $COUNTER)
	{
		$pagenation .= '<b>' . $COUNTER . '</b>&nbsp;&nbsp;';
	}
	else
	{
		$pagenation .= '<a href="active_auctions.php?PAGE=' . $COUNTER . '&user_id=' . $user_id . '"><u>' . $COUNTER . '</u></a>&nbsp;&nbsp;';
	}
	$COUNTER++;
}

$template->assign_vars(array(
		'B_MULPAG' => ($PAGES > 1),
		'B_NOTLAST' => ($PAGE < $PAGES),
		'B_NOTFIRST' => ($PAGE > 1),

		'USER_RSSFEED' => sprintf($MSG['932'], $TPL_user_nick),
		'USER_ID' => $user_id,
		'USERNAME' => $TPL_user_nick,
		'THUMBWIDTH' => $system->SETTINGS['thumb_show'],
		'NEXT' => intval($PAGE + 1),
		'PREV' => intval($PAGE - 1),
		'PAGE' => $PAGE,
		'PAGES' => $PAGES,
		'PAGENA' => $pagenation
		));

include 'header.php';
$template->set_filenames(array(
		'body' => 'active_auctions.tpl'
		));
$template->display('body');
include 'footer.php';
