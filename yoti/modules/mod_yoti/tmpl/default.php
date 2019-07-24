<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access'); // no direct access


// Don't show button until we have pem, Client SDK ID And Scenario ID.
$config = ModYotiHelper::getConfig();
if (!$config['yoti_sdk_id']
    || !$config['yoti_scenario_id']
    || !$config['yoti_pem']->contents
) {
    return;
}

$currentUser = JFactory::getUser();
$buttonId = ModYotiHelper::createButtonId();


// Add Yoti button library
$document = JFactory::getDocument();
$document->addScript(JUri::base() . 'components/com_yoti/assets/loader.js', array(), array('defer' => true));
$document->addStyleSheet(JUri::base() . 'components/com_yoti/assets/styles.css');
?>
<div class="yoti-connect">
    <?php if ($currentUser->guest || !YotiModelUser::yotiUserIsLinkedToJoomlaUser($currentUser->id)) : ?>
        <div id="<?php echo htmlspecialchars($buttonId); ?>" class="yoti-button"></div>
        <script>
        var yotiConfig = yotiConfig || { elements: [] };
        yotiConfig.elements.push(<?php echo json_encode(array(
            'domId' => htmlspecialchars($buttonId),
            'clientSdkId' =>  htmlspecialchars($config['yoti_sdk_id']),
            'scenarioId' =>  htmlspecialchars($config['yoti_scenario_id']),
            'button' => array(
                'label' => $currentUser->guest ? ModYotiHelper::YOTI_LINK_BUTTON_DEFAULT_TEXT : 'Link to Yoti',
            ),
        )); ?>);
        </script>
    <?php else : ?>
        <strong>Yoti</strong> Linked
    <?php endif; ?>
</div>
