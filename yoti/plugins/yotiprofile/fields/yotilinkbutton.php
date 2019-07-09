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
        if (isset($this->element['data-button-text'])) {
            $urlText = $this->element['data-button-text'];
        } else {
            $urlText = 'Unlink Yoti account';
        }

        return plgUserYotiprofile::yotilinkbutton($urlText);
    }

    public function getLabel()
    {
        return '';
    }
}
