<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access'); // no direct access

// Include the latest functions only once
JLoader::register('ModYotiHelper', __DIR__ . '/helper.php');

// Load YotiUserModel
JLoader::register('YotiModelUser', JPATH_ROOT . '/components/com_yoti/models/user.php');

require JModuleHelper::getLayoutPath('mod_yoti', $params->get('layout', 'default'));
