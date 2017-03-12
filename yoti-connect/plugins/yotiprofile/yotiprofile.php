<?php
/**
 * @version
 * @copyright    Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

use Yoti\ActivityDetails;

defined('JPATH_BASE') or die;
require_once JPATH_SITE . '/components/com_yoticonnect/sdk/boot.php';
require_once JPATH_SITE . '/components/com_yoticonnect/YotiConnectHelper.php';

/**
 * An example custom profile plugin.
 *
 * @package        Joomla.Plugins
 * @subpackage    user.profile
 * @version        1.6
 */
class plgUseryotiprofile extends JPlugin
{
    /**
     * @param    JForm    The form to be altered.
     * @param    array    The associated data for the form.
     * @return    boolean
     * @since    1.6
     */
    function onContentPrepareForm($form, $data)
    {
        // Load user_profile plugin language
        $lang = JFactory::getLanguage();
        $lang->load('plg_user_yotiprofile', JPATH_ADMINISTRATOR);

        if (!($form instanceof JForm))
        {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }
        // Check we are manipulating a valid form.
        $forms = array('com_users.profile', 'com_users.registration', 'com_users.user', 'com_admin.profile');
        if (!in_array($form->getName(), $forms))
        {
            return true;
        }

//        exit($form->getName());
        if ($form->getName() == 'com_users.profile' || $form->getName() == 'com_users.user')
        {
            JForm::addFieldPath(dirname(__FILE__) . '/fields');

            $user = JFactory::getUser($data->id);
            $db = JFactory::getDbo();
            $tableName = YotiConnectHelper::tableName();
            $dbProfile = $db->loadAssoc($db->setQuery("SELECT * FROM {$tableName} WHERE joomla_userid=" . $db->quote($user->id)));
            $profile = null;
            if ($dbProfile)
            {
                $profile = new ActivityDetails($dbProfile, $dbProfile['identifier']);
            }

            // display these fields
            $map = array(
                ActivityDetails::ATTR_SELFIE => 'Selfie',
                ActivityDetails::ATTR_PHONE_NUMBER => 'Phone number',
                ActivityDetails::ATTR_DATE_OF_BIRTH => 'Date of birth',
                ActivityDetails::ATTR_GIVEN_NAMES => 'Given names',
                ActivityDetails::ATTR_FAMILY_NAME => 'Family name',
                ActivityDetails::ATTR_NATIONALITY => 'Nationality',
            );
            if ($profile)
            {
                $xml = '';
                foreach ($map as $param => $label)
                {
                    $value = $profile->getProfileAttribute($param);
                    if ($param == ActivityDetails::ATTR_SELFIE)
                    {
                        $selfieFullPath = YotiConnectHelper::uploadDir() . "/{$dbProfile['selfie_filename']}";
                        if ($dbProfile['selfie_filename'] && file_exists($selfieFullPath))
                        {
                            $selfieUrl = JRoute::_('index.php?option=com_yoticonnect&task=bin-file&field=selfie');
//                                site_url('wp-login.php') . '?yoti-connect=1&action=bin-file&field=selfie';
//                            $selfieUrl = YotiConnectHelper::uploadUrl() . "/{$dbProfile['selfie_filename']}";
                            $xml .= '<field name="' . $param . '" type="Image" src="' . $selfieUrl . '" width="100" />';
                        }
                    }
                    else
                    {
                        $xml .= '<field name="' . $param . '" type="Static" label="' . $label . '" value="' . $value . '" />';
                    }
                }
                $xml = '<fieldset name="yotiprofile" label="Yoti Profile">' . $xml . '</fieldset>';
                $form->setField(new SimpleXMLElement($xml));
            }

            // todo: add this
            //            echo '<tr><th><label>Connect</label></th>';
            //            echo '<td>' . YotiConnectButton::render($_SERVER['REQUEST_URI']) . '</td></tr>';
            //            echo '</table>';

            // Add the profile fields to the form.
            JForm::addFormPath(dirname(__FILE__) . '/profiles');
            $form->loadFile('profile', false);

            // Toggle whether the something field is required.
            //            if ($this->params->get('profile-require_something', 1) > 0) {
            //                $form->setFieldAttribute('something', 'required', $this->params->get('profile-require_something') == 2, 'yotiprofile');
            //            } else {
            //                $form->removeField('something', 'yotiprofile');
            //            }
        }
        //
        //        //In this example, we treat the frontend registration and the back end user create or edit as the same.
        //        elseif ($form->getName()=='com_users.registration' || $form->getName()=='com_users.user' )
        //        {
        //            // Add the registration fields to the form.
        //            JForm::addFormPath(dirname(__FILE__).'/profiles');
        //            $form->loadFile('profile', false);
        //
        //            // Toggle whether the something field is required.
        //            if ($this->params->get('register-require_something', 1) > 0) {
        //                $form->setFieldAttribute('something', 'required', $this->params->get('register-require_something') == 2, 'yotiprofile');
        //            } else {
        //                $form->removeField('something', 'yotiprofile');
        //            }
        //        }
    }

    function onUserAfterSave($data, $isNew, $result, $error)
    {
        $userId = JArrayHelper::getValue($data, 'id', 0, 'int');

        if ($userId && $result && isset($data['yotiprofile']) && (count($data['yotiprofile'])))
        {
            try
            {
                $db = JFactory::getDbo();
                $db->setQuery('DELETE FROM #__user_profiles WHERE user_id = ' . $userId . ' AND profile_key LIKE \'yotiprofile.%\'');
                if (!$db->query())
                {
                    throw new Exception($db->getErrorMsg());
                }

                $tuples = array();
                $order = 1;
                foreach ($data['yotiprofile'] as $k => $v)
                {
                    $tuples[] = '(' . $userId . ', ' . $db->quote('yotiprofile.' . $k) . ', ' . $db->quote(json_encode($v)) . ', ' . $order++ . ')';
                }

                $db->setQuery('INSERT INTO #__user_profiles VALUES ' . implode(', ', $tuples));
                if (!$db->query())
                {
                    throw new Exception($db->getErrorMsg());
                }
            }
            catch (JException $e)
            {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Remove all user profile information for the given user ID
     *
     * Method is called after user data is deleted from the database
     *
     * @param    array $user Holds the user data
     * @param    boolean $success True if user was succesfully stored in the database
     * @param    string $msg Message
     */
    function onUserAfterDelete($user, $success, $msg)
    {
        if (!$success)
        {
            return false;
        }

        $userId = JArrayHelper::getValue($user, 'id', 0, 'int');

        if ($userId)
        {
            try
            {
                $db = JFactory::getDbo();
                $db->setQuery(
                    'DELETE FROM #__user_profiles WHERE user_id = ' . $userId .
                    " AND profile_key LIKE 'yotiprofile.%'"
                );

                if (!$db->query())
                {
                    throw new Exception($db->getErrorMsg());
                }
            }
            catch (JException $e)
            {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }


}