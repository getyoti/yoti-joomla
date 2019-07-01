<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

// The class name must always be the same as the filename (in camel case)
class JFormFieldStatic extends JFormField
{
    // The field class must know its own type through the variable $type.
    protected $type = 'Static';

    public function getInput()
    {

        $value = $this->element['value'];
        if (empty($value)) {
            $elementName = $this->element['name'];
            $data = $this->form->getData()->get('yotiprofile');
            if (is_object($data) && isset($data->{$elementName})) {
                $value = $data->{$elementName};
            }
        }

        $html = (string)$value ? htmlspecialchars($value) : '<i>(empty)</i>';
        return '<div class="form-control-static">' . $html . '</div>';
    }
}
