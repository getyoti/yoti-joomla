<?php
defined('_JEXEC') or die('Restricted access');

/**
 * Admin view class
 * @author Simon Tong <simon.tong@yoti.com>
 */
class AdminYotiConnectViewYotiConnect extends JViewLegacy
{
    /**
     * @var array
     */
    public $data = array();
    public $errors = array();

    /**
     * @param null $tpl
     * @return mixed|void
     * @throws Exception
     */
    public function display($tpl = null)
    {
        $this->addSidebar();
        JFactory::getDocument()->addStyleSheet("$this->baseurl/components/com_yoticonnect/assets/styles.css");

        /** @var stdClass $component */
        $component = JComponentHelper::getComponent('com_yoticonnect');
        /** @var \Joomla\Registry\Registry $config */
        $config = $component->params;

        // check has preliminary extensions to run
        $errors = array();
        if (!function_exists('curl_version'))
        {
            $errors[] = "PHP module 'curl' not installed. Yoti Connect requires it to work. Please contact your server administrator.";
        }
        if (!function_exists('mcrypt_encrypt'))
        {
            $errors[] = "PHP module 'mcrypt' not installed. Yoti Connect requires it to work. Please contact your server administrator.";
        }
        if (!function_exists('json_decode'))
        {
            $errors[] = "PHP module 'json' not installed. Yoti Connect requires it to work. Please contact your server administrator.";
        }

        // get data
        $data = $config;
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $data['yoti_app_id'] = $this->postVar('yoti_app_id');
            $data['yoti_sdk_id'] = $this->postVar('yoti_sdk_id');
            $data['yoti_delete_pem'] = ($this->postVar('yoti_delete_pem')) ? true : false;
            $pemFile = $this->filesVar('yoti_pem', (array)$config['yoti_pem']);

            // validation
            if (!$data['yoti_sdk_id'])
            {
                $errors['yoti_sdk_id'] = 'App ID is required.';
            }
            if (!$data['yoti_app_id'])
            {
                $errors['yoti_app_id'] = 'SDK ID is required.';
            }
            if (empty($pemFile['name']))
            {
                $errors['yoti_pem'] = 'PEM file is required.';
            }
            elseif (!empty($pemFile['tmp_name']) && !openssl_get_privatekey(file_get_contents($pemFile['tmp_name'])))
            {
                $errors['yoti_pem'] = 'PEM file is invalid.';
            }

            // no errors? proceed
            if ($errors)
            {
                foreach ($errors as $err)
                {
                    JFactory::getApplication()->enqueueMessage($err, 'error');
                }
            }
            else
            {
                // if pem file uploaded then process
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
                // if delete not ticked
                elseif (!$data['yoti_delete_pem'])
                {
                    $name = $config['yoti_pem']->name;
                    $pemContents = $config['yoti_pem']->contents;
                }

                // set pem file
                $data['yoti_pem'] = array(
                    'name' => $name,
                    'contents' => $pemContents,
                );

                // save config
                $config->set('yoti_app_id', $data['yoti_app_id']);
                $config->set('yoti_sdk_id', $data['yoti_sdk_id']);
                $config->set('yoti_pem', $data['yoti_pem']);

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

        /** @var AdminYotiConnectViewYotiConnect $view */
        $this->errors = $errors;
        $this->data = $data->flatten();

        return parent::display($tpl);
    }

    /**
     * @param $var
     * @param null $default
     * @return null
     */
    protected function postVar($var, $default = null)
    {
        return JFactory::getApplication()->input->post->get($var, $default);
//        return (array_key_exists($var, $_POST)) ? $_POST[$var] : $default;
    }

    /**
     * @param $var
     * @param null $default
     * @return null
     */
    protected function filesVar($var, $default = null)
    {
//        return JFactory::getApplication()->input->files->get($var, $default);
        return (array_key_exists($var, $_FILES) && !empty($_FILES[$var]['name'])) ? $_FILES[$var] : $default;
    }

    /**
     * add sidebar
     */
    private function addSidebar()
    {
        $view = JFactory::getApplication()->input->getCmd('view', 'yoticonnect');
        JHtmlSidebar::addEntry(
            'Yoti Connect',
            'index.php?option=com_yoticonnect&view=yoticonnect',
            ($view == 'yoticonnect')
        );
        JHtmlSidebar::addEntry(
            'Users',
            'index.php?option=com_yoticonnect&view=users',
            ($view == 'users')
        );
        JToolbarHelper::preferences('com_yoticonnect', 400, 570);
        JToolbarHelper::title('Yoti Connect');
    }
}