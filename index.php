<?php /* $Id$ $URL$ */
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly.');
}

$AppUI->savePlace();

$tab = $AppUI->processIntState('HolidayTab', $_GET, 'tab', 0);

// Create module header
$titleBlock = new w2p_Theme_TitleBlock('Working time', 'myevo-appointments.png', $m, $m . '.' . $a);
$titleBlock->show();

// tabbed information boxes
$tabBox = new CTabBox("?m=$m", W2P_BASE_DIR . "/modules/$m/", $tab);
if (canEdit('admin')) $tabBox->add("holiday_settings", "Company working time");
$tabBox->add("holiday", "User holidays");
$tabBox->show();