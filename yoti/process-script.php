<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access'); // no direct access

/**
 * Class com_YotiInstallerScript
 *
 * @author Moussa Sidibe <sdksupport@yoti.com>
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
        // Leave it empty
    }
}