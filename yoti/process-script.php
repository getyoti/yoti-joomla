<?php
defined('_JEXEC') or die('Restricted access'); // no direct access

/**
 * Class com_YotiInstallerScript
 *
 * @author Moussa Sidibe <moussa.sidibe@yoti.com>
 */
class com_yotiInstallerScript
{
    /**
     * @param JAdapterInstance $adapter
     */
    public function install(JAdapterInstance $adapter)
    {
        $app = JFactory::getApplication();
        $modulePath = __DIR__ . '/modules/mod_yoti';
        $installer = new JInstaller;

        if (is_dir($modulePath))
        {
            try {
                $installer->install($modulePath);
                $app->enqueueMessage('Installing module [mod_yoti] was successful.', 'message');
            } catch(\Exception $e) {
                $app->enqueueMessage('Error - Installing module [mod_yoti] ' . $e->getMessage(), 'error');
            }
        }
        else
        {
            $app->enqueueMessage('Installing module [mod_yoti] failed.', 'error');
        }

        $pluginPath = __DIR__ . '/plugins/yotiprofile';
        if (is_dir($pluginPath))
        {
            try {
                $installer->install($pluginPath);
                $app->enqueueMessage('Installing plugin [yotiprofile] was successful.', 'message');
            } catch(\Exception $e) {
                $app->enqueueMessage('Error - Installing plugin [yotiprofile] ' . $e->getMessage(), 'error');
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
        $installer = new JInstaller;

        $moduleQuery = $db->getQuery(true)
                ->select('extension_id')
                ->from('#__extensions')
                ->where($db->quoteName('element') . '=' . $db->quote('mod_yoti'))
                 ->where($db->quoteName('type') . '=' . $db->quote('module'));
        $moduleId = $db->setQuery($moduleQuery)->loadResult();
        if ($moduleId)
        {
            try {
                $installer->uninstall('module', $moduleId, 1);
                $app->enqueueMessage('Uninstalling module [mod_yoti] was successful.', 'message');
            } catch(\Exception $e) {
                $app->enqueueMessage("Error uninstalling module [mod_yoti] " . $e->getMessage(), 'error');
            }
        }
        $pluginQuery = $db->getQuery(true)
            ->select('extension_id')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . '=' . $db->quote('yotiprofile'))
            ->where($db->quoteName('type') . '=' . $db->quote('plugin'));
        $pluginId = $db->setQuery($pluginQuery)->loadResult();
        if($pluginId)
        {
            try {
                $installer->uninstall('plugin', $pluginId, 1);
                $app->enqueueMessage('Uninstalling plugin [plg_user_yotiprofile] was successful.', 'message');
            } catch(\Exception $e) {
                $app->enqueueMessage("Error uninstalling plugin [plg_user_yotiprofile] " . $e->getMessage(), 'error');
            }
        }
    }
}