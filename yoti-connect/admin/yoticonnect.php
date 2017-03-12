<?php
defined('_JEXEC') or die; // No direct access

require_once JPATH_SITE . '/components/com_yoticonnect/sdk/boot.php';

$controller = JControllerLegacy::getInstance('AdminYotiConnect');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();