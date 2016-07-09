<?php

if ( ! defined('REESERVATION_ADDON_NAME'))
{
	define('REESERVATION_ADDON_NAME',         'rEEservation');
	define('REESERVATION_ADDON_VERSION',      '2.7.7');
}

$config['name'] = REESERVATION_ADDON_NAME;
$config['version'] = REESERVATION_ADDON_VERSION;

$config['nsm_addon_updater']['versions_xml']='http://www.intoeetive.com/index.php/update.rss/27';