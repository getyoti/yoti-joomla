<?php
/**
 * @package    PlgUserCMAvatar
 * @copyright  Copyright (C) 2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('file');

/**
 * Form field for avatar.
 *
 * @package  PlgUserYotiAvatar
 * @since    1.0.0
 */
class JFormFieldYotiAvatar extends JFormFieldFile
{
    /**
     * The form field type.
     *
     * @var    string
     *
     * @since  1.0.0
     */
    protected $type = 'YotiAvatar';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.0.0
     */
    protected function getInput()
    {
        if (empty($this->value))
        {
            $currentAvatar = JText::_('PLG_USER_CMAVATAR_NO_AVATAR');
        }
        else
        {
            $srcValue = JRoute::_('index.php?option=com_yoti&task=bin-file&field=selfie');
            $width = (isset($this->element['width'])) ? $this->element['width'] : 100;
            $value = JText::_('PLG_USER_YOTIPROFILE_FIELD_SELFIE_FILENAME_ALT');
            $currentAvatar = '<img src="' . $srcValue . '" width="' . $width . ' alt="' . trim($value). '" />';
        }

        return $currentAvatar;
    }
}
