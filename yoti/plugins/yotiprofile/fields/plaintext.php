<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

// The class name must always be the same as the filename (in camel case)
class JFormFieldPlaintext extends JFormField
{
    //The field class must know its own type through the variable $type.
    protected $type = 'Plaintext';

    public function getInput()
    {
        $config = YotiHelper::getConfig();
        $companyName = isset($config['yoti_company_name']) ? $config['yoti_company_name'] : 'Joomla';
        $warningMsg = '<strong>Warning</strong>: You are about to link your <strong>' .
            $companyName . '</strong> account to your Yoti account.<br/> Click the box below to keep them separate.';
        $html = '<span style="font-size:14px;">'.$warningMsg. '</span>';
        return $html;
    }

    public function getLabel()
    {
        return '';
    }
}