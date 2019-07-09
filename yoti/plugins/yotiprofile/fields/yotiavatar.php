<?php
/**
 * @package    PlgUserYotiavatar
 * @copyright  Copyright (C) 2016 YotiExtension Team http://www.yoti.com/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

jimport('joomla.form.formfield');

/**
 * Form field for avatar.
 *
 * @package  PlgUserYotiAvatar
 * @since    1.0.0
 */
class JFormFieldYotiAvatar extends JFormField
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
        if (empty($this->value)) {
            $currentAvatar = JText::_('PLG_USER_CMAVATAR_NO_AVATAR');
        } else {
            $width = isset($this->element['width']) ? $this->element['width'] : 100;
            $currentAvatar = plgUserYotiprofile::yotiavatar($this->value, $width);
        }

        return $currentAvatar;
    }
}
