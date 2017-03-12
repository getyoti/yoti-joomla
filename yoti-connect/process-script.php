<?php
defined('_JEXEC') or die('Restricted access'); // no direct access

/**
 * Class com_YotiConnectInstallerScript
 *
 * @author Simon Tong <simon.tong@yoti.com>
 */
class com_yoticonnectInstallerScript
{
    /**
     * @param JAdapterInstance $adapter
     */
    public function install(JAdapterInstance $adapter)
    {
        $app = JFactory::getApplication();
        $modulePath = __DIR__ . '/modules/mod_yoticonnect';
        if (is_dir($modulePath))
        {
            $installer = new JInstaller;
            if ($installer->install($modulePath))
            {
                $app->enqueueMessage('Installing module [mod_yoticonnect] was successful.', 'message');
            }
            else
            {
                $app->enqueueMessage('Installing module [mod_yoticonnect] failed.', 'error');
            }
        }
        else
        {
            $app->enqueueMessage('Installing module [mod_yoticonnect] failed.', 'error');
        }

        $pluginPath = __DIR__ . '/plugins/yotiprofile';
        if (is_dir($pluginPath))
        {
            $installer = new JInstaller;
            if ($installer->install($pluginPath))
            {
                $app->enqueueMessage('Installing plugin [yotiprofile] was successful.', 'message');
            }
            else
            {
                $app->enqueueMessage('Installing plugin [yotiprofile] failed.', 'error');
            }
        }
        else
        {
            $app->enqueueMessage('Installing plugin [yotiprofile] failed - file not found.', 'error');
        }
    }

    /**
     * @param JAdapterInstance $adapter
     */
    public function uninstall(JAdapterInstance $adapter)
    {
        $db = JFactory::getDBO();
        $app = JFactory::getApplication();

        $db->setQuery("SELECT `extension_id` FROM #__extensions WHERE `element` = 'mod_yoticonnect' AND `type` = 'module'");
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new JInstaller;
            if ($installer->uninstall('module', $id, 1))
            {
                $app->enqueueMessage('Uninstalling module [mod_yoticonnect] was successful.', 'message');
            }
            else
            {
                $app->enqueueMessage('Uninstalling module [mod_yoticonnect] failed.', 'error');
            }
        }
    }
}