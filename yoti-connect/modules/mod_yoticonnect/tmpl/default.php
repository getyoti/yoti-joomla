<?php
defined('_JEXEC') or die('Restricted access'); // no direct access

require_once JPATH_SITE . '/components/com_yoticonnect/sdk/boot.php';
require_once JPATH_SITE . '/components/com_yoticonnect/YotiConnectHelper.php';

// don't show button until we have pem and id
$config = YotiConnectHelper::getConfig();
if (!$config['yoti_sdk_id'] || !$config['yoti_pem']->contents)
{
    return;
}

$testToken = null;
if (YotiConnectHelper::mockRequests())
{
    $testToken = file_get_contents(JPATH_SITE.'/components/com_yoticonnect/sdk/sample-data/connect-token.txt');
}

$db = JFactory::getDbo();
$currentUser = JFactory::getUser();

if ($currentUser->guest)
{
    if (YotiConnectHelper::mockRequests())
    {
        $url = JRoute::_('index.php?option=com_yoticonnect&task=login&token=' . $testToken);
    }
    else
    {
        $url = YotiConnectHelper::getLoginUrl();
    }
    $label = 'Sign on with Yoti';
}
else
{
    $db->setQuery("SELECT joomla_userid FROM #__yoti_users WHERE joomla_userid = '$currentUser->id'");
    $id = $db->loadResult();
    if (!$id)
    {
        if (YotiConnectHelper::mockRequests())
        {
            $url = JRoute::_('index.php?option=com_yoticonnect&task=login&token=' . $testToken);
        }
        else
        {
            $url = YotiConnectHelper::getLoginUrl();
        }
        $label = 'Link account to Yoti';
    }
    else
    {
        $url = JRoute::_('index.php?option=com_yoticonnect&task=unlink');
        $label = 'Unlink account from Yoti';
    }
}

echo '<a href="' . $url . '" id="yoti-connect-button">' . $label . '</a>';