<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

// The class name must always be the same as the filename (in camel case)
class JFormFieldYotilinkbutton extends JFormField
{
    // The field class must know its own type through the variable $type.
    protected $type = 'Yotilinkbutton';

    public function getInput()
    {
        $urlText = isset($this->element['data-button-text']) ? $this->element['data-button-text'] : 'Unlink Yoti account';
        $urlLink = JRoute::_('index.php?option=com_yoti&task=unlink');
        $promptMessage = JText::_('PLG_USER_YOTIPROFILE_UNLINK_ACCOUNT_BUTTON_PROMPT_MESSAGE');
        $html = '<div class="yoti-connect">' .
            "<a class=\"yoti-unlink-button\" onclick=\"return confirm('{$promptMessage}')\" href=\"$urlLink\">" .
            JText::_($urlText) .
            '</a></div>';
        return $html;
    }

    public function getLabel()
    {
        return '';
    }
}