<?php
/**
 * @version
 * @copyright    Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license        GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

require_once JPATH_ROOT . '/components/com_yoti/sdk/boot.php';
require_once JPATH_ROOT . '/components/com_yoti/YotiHelper.php';

// Load the Joomla Model framework
jimport('joomla.application.component.model');
// Load YotiUserModel
JLoader::register('YotiModelUser', JPATH_ROOT . '/components/com_yoti/models/user.php');

jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

/**
 * UserYotiprofile plugin.
 *
 * @package        Joomla.Plugins
 * @subpackage    user.profile
 * @version        2.5
 */
class plgUserYotiprofile extends JPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     *
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Constructor.
     *
     * @param   object  &$subject  The object to observe
     * @param   array   $config    An array that holds the plugin configuration
     *
     * @since   1.0.0
     */
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        JFormHelper::addFieldPath(__DIR__ . '/fields');
    }

    /**
     * @param	string	The context for the data
     * @param	int		The user id
     * @param	object
     * @return	boolean
     * @since	2.5
     */
    public function onContentPrepareData($context, $data)
    {
        // Check we are manipulating a valid form.
        if (!in_array($context, ['com_users.profile','com_users.registration','com_users.user','com_admin.profile'], TRUE)){
            return true;
        }

        $userId = isset($data->id) ? $data->id : 0;

        // Load the profile data from the database.
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('data')
            ->from($db->quoteName(YotiHelper::YOTI_USER_TABLE_NAME))
            ->where($db->quoteName('joomla_userid') . '=' . $db->quote($userId))
            ->setLimit('1');
        $result = $db->setQuery($query)->loadAssoc();

        // Check for a database error.
        if ($db->getErrorNum()) {
            $this->_subject->setError($db->getErrorMsg());
            return false;
        }

        // Merge the profile data.
        $data->yotiprofile = [];
        $profileArr = (!empty($result['data'])) ? unserialize($result['data']) : [];

        foreach ($profileArr as $key => $value) {
            $data->yotiprofile[$key] = $value;
        }

        // Set the unlink account message if we have profile data
        if(!empty($data->yotiprofile)) {
            $data->yotiprofile = $this->setUnlinkButtonMessage($data->yotiprofile);
        }

        // Register Yoti link button
        if (!JHtml::isRegistered('users.yotilinkbutton'))
        {
            JHtml::register('users.yotilinkbutton', [__CLASS__, 'yotilinkbutton']);
        }

        // Register Yoti avatar
        if (!JHtml::isRegistered('users.yotiavatar'))
        {
            JHtml::register('users.yotiavatar', [__CLASS__, 'yotiavatar']);
        }

        // Register Yoti spacer
        if (!JHtml::isRegistered('users.yotispacer'))
        {
            JHtml::register('users.yotispacer', [__CLASS__, 'yotispacer']);
        }

        return true;
    }

    /**
     * Add unlink button to yotiprofile data
     * @param array $yotiprofile
     * @return array
     */
    protected function setUnlinkButtonMessage(array $yotiprofile)
    {

        $yotiprofile['yoti_unlink_account'] = JText::_('PLG_USER_YOTIPROFILE_FIELD_UNLINK_ACCOUNT_LABEL');

        return $yotiprofile;
    }

    /**
     * @param    JForm    The form to be altered.
     * @param    array    The associated data for the form.
     * @return    boolean
     * @since    1.6
     */
    public function onContentPrepareForm($form, $data)
    {
        $config = YotiHelper::getConfig();

        if (!($form instanceof JForm))
        {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }

        if (
            $config['yoti_only_existing_user']
            && $form->getName() === 'com_users.login'
            && null !== YotiHelper::getYotiUserFromSession()
        ) {
            // Reorder the form to put the warning message on top
            JForm::addFieldPath(__DIR__ . '/fields');
            if($yotiLoginXml = simplexml_load_string(file_get_contents(__DIR__ . "/profiles/login.xml"))){
                $formXml = $form->getXML();
                $form->reset(true);
                $form->setFields($yotiLoginXml);
                $form->setFields($formXml);
            }
        }

        // Check we are manipulating a valid form.
        $formNames = ['com_users.profile', 'com_users.registration', 'com_users.user', 'com_admin.profile'];
        if (!in_array($form->getName(), $formNames, TRUE))
        {
            return true;
        }

        if (
            !empty($data->yotiprofile)
            && ($form->getName() === 'com_users.profile'
            || $form->getName() === 'com_users.user')
        )
        {
            JForm::addFormPath(__DIR__ . '/profiles');
            $form->loadFile('profile', false);

            // Remove the spacer and unlink button in profile edit mode
            if (isset($_REQUEST['layout']) && $_REQUEST['layout'] == 'edit') {
                $form->removeField('yotispacer', 'yotiprofile');
                $form->removeField('yoti_unlink_account', 'yotiprofile');
            } else {
                // Remove yoti_user_notice attribute if we are not in edit mode
                $form->removeField('yoti_user_notice', 'yotiprofile');
            }
        }

        return true;
    }

    /**
     * Returns Yoti button link
     *
     * @param $value
     * @return string
     */
    public static function yotilinkbutton($value)
    {
        $urlLink = JRoute::_('index.php?option=com_yoti&task=unlink');
        $promptMessage = JText::_('PLG_USER_YOTIPROFILE_UNLINK_ACCOUNT_BUTTON_PROMPT_MESSAGE');
        $html = '<div class="yoti-connect">' .
            "<a class=\"yoti-unlink-button\" onclick=\"return confirm('{$promptMessage}')\" href=\"$urlLink\">" .
            JText::_($value) .
            '</a></div>';

        return $html;
    }

    /**
     * Returns Yoti user profile image.
     *
     * @param $value
     * @return mixed
     */
    public static function yotiavatar($value)
    {
        $srcValue = JRoute::_('index.php?option=com_yoti&task=bin-file&field=selfie');
        $width = 100;
        $avatarHTML = JHtml::_('image', trim(JUri::base(), '/') . $srcValue, 'Your Selfie', ['width'=>$width]);

        return $avatarHTML;
    }

    /**
     * Returns line break.
     *
     * @param $value
     * @return string
     */
    public static function yotispacer($value)
    {
        return '<br/>';
    }

    /**
     * Remove all user profile information for the given user ID
     * Method is called after user data is deleted from the database
     *
     * @param $user
     * @param $success
     * @param $msg
     * @return bool
     */
    public function onUserAfterDelete($user, $success, $msg)
    {
        if (!$success)
        {
            return false;
        }

        $yotiUserModel = new YotiModelUser();

        $userId = (isset($user['id'])) ? $user['id'] : 0;

        if ($userId)
        {
            try
            {
                if ($yotiUserModel->getYotiUserById($userId)) {
                    $yotiUserModel->deleteYotiUser($userId);
                }
            }
            catch (\Exception $e)
            {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Triggered after user login process
     *
     * @param array $user
     * @param array $options
     */
    public function onUserLogin($user, $options) {
        if(!YotiHelper::getYotiUserFromSession()) {
            $yotiUserModel = new YotiModelUser();
            $yotiUserData = $yotiUserModel->getYotiUserById($user['id']);
            if(!empty($yotiUserData) && isset($yotiUserData['data'])) {
                // After successful login store Yoti user data in the session
                $yotiuserProfile = YotiHelper::makeYotiUserProfile(unserialize($yotiUserData['data']), $user['id']);
                YotiHelper::storeYotiUserInSession($yotiuserProfile);
            }
        }
    }

    /**
     * Create or delete Yoti user from Joomla.
     * Method is called after a user has logged in.
     *
     * @param $options
     * @return bool
     */
    public function onUserAfterLogin($options)
    {
        $input  = JFactory::getApplication()->input;
        $user = $options['user'];
        $userId = is_object($user) ? $user->id : 0;
        $yotiUserModel = new YotiModelUser();

        if ($input->post) {
            $postData = $input->post->getArray();
            // If Yoti nolink option is ticked then remove Yoti user
            if (isset($postData['credentials']['yoti_nolink']) && $input->post->get('credentials')) {
                try {
                    if($yotiUserModel->getYotiUserById($userId)) {
                       $yotiUserModel->deleteYotiUser($userId);
                    }
                } catch(\Exception $e) {
                    $this->_subject->setError($e->getMessage());
                    return false;
                }
            } else if (YotiHelper::getYotiUserFromSession()) {
                // If the session is set then create Yoti user.
                $activityDetails = YotiHelper::getYotiUserFromSession();

                if ($activityDetails) {
                    try {
                        $yotiHelper = new YotiHelper();
                        $yotiHelper->createYotiUser($activityDetails, $userId);
                    } catch(\Exception $e) {
                        $this->_subject->setError($e->getMessage());
                        return false;
                    }
                }
            }
            YotiHelper::clearYotiUserFromSession();
        }

        return true;
    }

}