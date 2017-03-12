<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

// The class name must always be the same as the filename (in camel case)
class JFormFieldImage extends JFormField
{
    //The field class must know its own type through the variable $type.
    protected $type = 'Image';

    public function getInput()
    {
        $value = '<img src="' . $this->element['src'] . '" width="' . $this->element['width'] . '" />';
        return '<div class="form-control-static">' . $value . '</div>';
    }
}