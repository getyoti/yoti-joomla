<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
defined('_JEXEC') or die; // No direct access

require_once JPATH_SITE . '/components/com_yoti/sdk/boot.php';

$controller = JControllerLegacy::getInstance('AdminYoti');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();