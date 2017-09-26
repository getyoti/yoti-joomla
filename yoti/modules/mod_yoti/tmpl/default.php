<?php
defined('_JEXEC') or die('Restricted access'); // no direct access

require_once JPATH_SITE . '/components/com_yoti/sdk/boot.php';
require_once JPATH_SITE . '/components/com_yoti/YotiHelper.php';

// don't show button until we have pem and id
$config = YotiHelper::getConfig();
if (!$config['yoti_sdk_id'] || !$config['yoti_pem']->contents)
{
    return;
}

$testToken = null;
if (YotiHelper::mockRequests())
{
    $testToken = file_get_contents(JPATH_SITE.'/components/com_yoti/sdk/sample-data/connect-token.txt');
}

$db = JFactory::getDbo();
$currentUser = JFactory::getUser();

if ($currentUser->guest)
{
    if (YotiHelper::mockRequests())
    {
        $url = JRoute::_('index.php?option=com_yoti&task=login&token=' . $testToken);
    }
    else
    {
        $url = YotiHelper::getLoginUrl();
    }
    $label = 'Sign on with Yoti';
}
else
{
    $db->setQuery("SELECT joomla_userid FROM #__yoti_users WHERE joomla_userid = '$currentUser->id'");
    $id = $db->loadResult();
    if (!$id)
    {
        if (YotiHelper::mockRequests())
        {
            $url = JRoute::_('index.php?option=com_yoti&task=login&token=' . $testToken);
        }
        else
        {
            $url = YotiHelper::getLoginUrl();
        }
        $label = 'Link account to Yoti';
    }
    else
    {
        $url = JRoute::_('index.php?option=com_yoti&task=unlink');
        $label = 'Unlink account from Yoti';
    }
}

echo '<a href="' . $url . '" id="yoti-connect-button">' . $label . '</a>';