<?php
defined('_JEXEC') or die('Restricted access');

/**
 * Admin view class
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @author Moussa Sidibe <sdksupport@yoti.com>
 */
class AdminYotiViewYoti extends JViewLegacy
{
    /**
     * @var array
     */
    public $data = array();
    public $errors = array();

    protected $defaultSuccessUrl = 'index.php?option=com_users&view=profile';
    protected $defaultFailedUrl = '/';
    protected $joomlaLoginPage = 'index.php?option=com_yoti&task=login';

    protected $formRequiredFields = [
        'yoti_sdk_id' => 'App ID',
        'yoti_app_id' => 'Scenario ID',
        'yoti_scenario_id' => 'SDK ID',
        'yoti_success_url' => 'Success URL',
        'yoti_failed_url' => 'Failed URL'
    ];

    /**
     * @param null $tpl
     * @return mixed|void
     * @throws Exception
     */
    public function display($tpl = null)
    {
        $this->addSidebar();
        JFactory::getDocument()->addStyleSheet("$this->baseurl/components/com_yoti/assets/styles.css");

        /** @var stdClass $component */
        $component = JComponentHelper::getComponent('com_yoti');
        /** @var \Joomla\Registry\Registry $config */
        $config = $component->params;

        // Check that the dependency requirements are met
        $errors = array();
        if (!function_exists('curl_version'))
        {
            $errors[] = "PHP module 'curl' not installed. Yoti requires it to work. Please contact your server administrator.";
        }
        if (!function_exists('mcrypt_encrypt'))
        {
            $errors[] = "PHP module 'mcrypt' not installed. Yoti requires it to work. Please contact your server administrator.";
        }
        if (!function_exists('json_decode'))
        {
            $errors[] = "PHP module 'json' not installed. Yoti requires it to work. Please contact your server administrator.";
        }

        // Get config data
        $data = $config;
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $errorMsg = $this->validateForm();
            $pemFile = $this->filesVar('yoti_pem', (array)$config['yoti_pem']);

            $data['yoti_app_id'] = $this->postVar('yoti_app_id');
            $data['yoti_scenario_id'] = $this->postVar('yoti_scenario_id');
            $data['yoti_sdk_id'] = $this->postVar('yoti_sdk_id');
            $data['yoti_company_name'] = $this->postVar('yoti_company_name');
            $data['yoti_delete_pem'] = ($this->postVar('yoti_delete_pem')) ? true : false;
            $data['yoti_success_url'] = $this->postVar('yoti_success_url');
            $data['yoti_failed_url'] = $this->postVar('yoti_failed_url');
            $data['yoti_only_existing_user'] = ($this->postVar('yoti_only_existing_user')) ? true : false;
            $data['yoti_user_email'] = ($this->postVar('yoti_user_email')) ? true : false;

            // Validation
            if(!empty($errorMsg)) {
                $errors['validation_error'] = $errorMsg;
            }
            elseif (empty($pemFile['name']))
            {
                $errors['yoti_pem'] = 'PEM file is required.';
            }
            elseif (!empty($pemFile['tmp_name']) && !openssl_get_privatekey(file_get_contents($pemFile['tmp_name'])))
            {
                $errors['yoti_pem'] = 'PEM file is invalid.';
            }

            // If there is no errors? proceed
            if ($errors)
            {
                foreach ($errors as $err)
                {
                    JFactory::getApplication()->enqueueMessage($err, 'error');
                }
            }
            else
            {
                // If pem file uploaded then process
                $name = $pemContents = null;
                if (!empty($pemFile['tmp_name']))
                {
                    $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $pemFile['name']);
                    if (!$name)
                    {
                        $name = md5($pemFile['name']) . '.pem';
                    }
                    $pemContents = file_get_contents($pemFile['tmp_name']);
                }
                // If "Delete this PEM file" not ticked
                elseif (!$data['yoti_delete_pem'])
                {
                    $name = $config['yoti_pem']->name;
                    $pemContents = $config['yoti_pem']->contents;
                }

                // Set pem file
                $data['yoti_pem'] = array(
                    'name' => $name,
                    'contents' => $pemContents,
                );

                // Save config data
                $config->set('yoti_app_id', $data['yoti_app_id']);
                $config->set('yoti_scenario_id', $data['yoti_scenario_id']);
                $config->set('yoti_sdk_id', $data['yoti_sdk_id']);
                $config->set('yoti_company_name', $data['yoti_company_name']);
                $config->set('yoti_pem', $data['yoti_pem']);
                $config->set('yoti_success_url', $data['yoti_success_url']);
                $config->set('yoti_failed_url', $data['yoti_failed_url']);
                $config->set('yoti_only_existing_user', $data['yoti_only_existing_user']);
                $config->set('yoti_user_email', $data['yoti_user_email']);

                $table = JTable::getInstance('extension');
                $table->load($component->id);
                $table->bind(array('params' => $config->toString()));
                if (!$table->store(true))
                {
                    JFactory::getApplication()->enqueueMessage("Couldn't save settings.", 'error');
                }
                else
                {
                    JFactory::getApplication()->enqueueMessage('Settings saved.');
                }
            }
        }

        $this->errors = $errors;
        $this->data = $data->flatten();

        $this->successUrl = (!empty($this->data['yoti_success_url'])) ? $this->data['yoti_success_url']  : $this->defaultSuccessUrl;
        $this->failedUrl = (!empty($this->data['yoti_failed_url'])) ? $this->data['yoti_failed_url']  : $this->defaultFailedUrl;
        $this->pemFilechecked = (!empty($this->data['yoti_delete_pem']) ? ' checked="checked"' : '');
        $this->onlyExitingUserChecked = (!empty($this->data['yoti_only_existing_user'])) ? ' checked="checked"'  : '';
        $this->userEmailChecked = (!empty($this->data['yoti_user_email'])) ? ' checked="checked"'  : '';


        return parent::display($tpl);
    }

    /**
     * Get post param value.
     *
     * @param $var
     * @param mixed $default
     *
     * @return mixed
     */
    protected function postVar($var, $default = null)
    {
        return JFactory::getApplication()->input->get($var, $default, 'STR');
    }

    /**
     * Validate form field.
     *
     * @return string
     */
    protected function validateForm()
    {
       $errorMsg = '';
       foreach($this->formRequiredFields as $fieldName => $fieldLabel) {
           if(empty($this->postVar($fieldName))) {
               $errorMsg = "{$fieldLabel} is required!";
               break;
           }
       }

       return $errorMsg;
    }

    /**
     * Get value fo the file.
     *
     * @param string $var
     * @param mixed $default
     *
     * @return mixed
     */
    protected function filesVar($var, $default = null)
    {
        return (array_key_exists($var, $_FILES) && !empty($_FILES[$var]['name'])) ? $_FILES[$var] : $default;
    }

    /**
     * Add sidebar.
     */
    private function addSidebar()
    {
        /** @var AdminYotiViewYoti $view */
        $view = JFactory::getApplication()->input->getCmd('view', 'yoti');
        JHtmlSidebar::addEntry(
            'Yoti',
            'index.php?option=com_yoti&view=yoti',
            ($view == 'yoti')
        );
        JHtmlSidebar::addEntry(
            'Users',
            'index.php?option=com_yoti&view=users',
            ($view == 'users')
        );
        JToolbarHelper::preferences('com_yoti', 400, 570);
        JToolbarHelper::title('Yoti Settings');
    }
}