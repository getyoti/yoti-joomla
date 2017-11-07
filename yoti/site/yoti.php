<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
defined('_JEXEC') or die; // No direct access

$controller = JControllerLegacy::getInstance('Yoti');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();