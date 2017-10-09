<?php
defined('_JEXEC') or die('Restricted access'); // no direct access

use Yoti\YotiClient;

// Don't show button until we have pem, SDK ID And Scenario ID.
$config = ModYotiHelper::getConfig();
if (
    !$config['yoti_sdk_id']
    || !$config['yoti_scenario_id']
    || !$config['yoti_pem']->contents
)
{
    return;
}

$testToken = null;
if (ModYotiHelper::mockRequests())
{
    $testToken = file_get_contents(JPATH_SITE.'/components/com_yoti/sdk/sample-data/connect-token.txt');
}

$currentUser = JFactory::getUser();

$document = JFactory::getDocument();
$document->addScript(ModYotiHelper::YOTI_BUTTON_JS_LIBRARY);

$script = [];

// If connect url starts with 'https://staging' then we are in staging mode.
$isStaging = strpos(YotiClient::CONNECT_BASE_URL, 'https://staging') === 0;
if ($isStaging) {
    // Base url for connect.
    $baseUrl = preg_replace('/^(.+)\/connect$/', '$1', YotiClient::CONNECT_BASE_URL);
    $yotiButtonJsVersion = YotiHelper::YOTI_BUTTON_JS_LIBRARY_VERSION;
    $yotiButtonQrVersion = (!empty($yotiButtonJsVersion)) ? $yotiButtonJsVersion . '/' : '';
    $script[] = sprintf('_ybg.config.qr = "%s/qr/' . $yotiButtonQrVersion . '";', $baseUrl);
    $script[] = sprintf('_ybg.config.service = "%s/connect/";', $baseUrl);
}

// Add init()
$script[] = '_ybg.init();';
$linkButton = '<span
            data-yoti-application-id="' . $config['yoti_app_id'] . '"
            data-yoti-type="inline"
            data-yoti-scenario-id="' . $config['yoti_scenario_id'] . '"
            data-size="small">
            %s
        </span>
        <script>' . implode("\r\n", $script) . '</script>';

if ($currentUser->guest)
{
    if (ModYotiHelper::mockRequests())
    {
        $url = JRoute::_('index.php?option=com_yoti&task=login&token=' . $testToken);
    }
    else
    {
        $url = ModYotiHelper::getLoginUrl();
    }

    $button = sprintf($linkButton, ModYotiHelper::YOTI_LINK_BUTTON_DEFAULT_TEXT);
}
else
{
    if (!YotiModelUser::yotiUserIsLinkedToJoomlaUser($currentUser->id))
    {
        if (ModYotiHelper::mockRequests())
        {
            $url = JRoute::_('index.php?option=com_yoti&task=login&token=' . $testToken);
        }
        else
        {
            $url = ModYotiHelper::getLoginUrl();
        }
        $button = sprintf($linkButton, 'Link Yoti account');
    }
    else
    {
        $url = JRoute::_('index.php?option=com_yoti&task=unlink');
        $label = 'Unlink account from Yoti';
        $button = '<a class="yoti-connect-button btn btn-primary" href="' . $url . '">' . $label . '</a>';
    }
}
echo '<div class="yoti-connect">' . $button . '</div>';