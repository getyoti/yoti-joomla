<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

// The class name must always be the same as the filename (in camel case)
class JFormFieldStatic extends JFormField
{
    //The field class must know its own type through the variable $type.
    protected $type = 'Static';

    public function getInput()
    {
        $value = (string)$this->element['value'] ? htmlspecialchars($this->element['value']) : '<i>(empty)</i>';

        return '<div class="form-control-static">' . $value . '</div>';
    }
}