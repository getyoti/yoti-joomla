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
        /*if(empty($value)){
            $elementName = $this->element['name'];
            $data = $this->form->getData()->get('yotiprofile');
            if (is_object($data) && isset($data->{$elementName})) {
                $value = $data->{$elementName};
            }
        }*/
        //$srcValue = (isset($this->element['src'])) ? $this->element['src'] : $value;
        $srcValue = JRoute::_('index.php?option=com_yoti&task=bin-file&field=selfie');
        $width = (isset($this->element['width'])) ? $this->element['width'] : 100;
        $html = '<img src="' . $srcValue . '" width="' . $width . '" />';
        return '<div class="form-control-static">' . $html . '</div>';
    }
}