<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

// The class name must always be the same as the filename (in camel case)
class JFormFieldYotinote extends JFormField
{
    //The field class must know its own type through the variable $type.
    protected $type = 'Yotinote';

    public function getInput()
    {
        $html = '<div style="font-size:14px;margin-bottom:15px;">' .
            '<strong>Notice</strong>:&nbsp;' .
            JText::_($this->element['value']).
            '</div>';
        return $html;
    }

    public function getLabel()
    {
        return '';
    }
}