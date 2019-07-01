<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
defined('_JEXEC') or die; // No direct access

require_once JPATH_SITE.'/components/com_yoti/YotiHelper.php';
require_once JPATH_SITE . '/components/com_yoti/sdk/boot.php';
JPluginHelper::importPlugin('user');

/**
 * Class YotiController
 *
 * @author Moussa Sidibe <sdksupport@yoti.com>
 */
class YotiController extends JControllerLegacy
{
    /**
     * User profile page
     */
    const USER_PROFILE_PAGE = 'index.php?option=com_users&view=profile';

    /**
     * @param bool $cachable
     * @param array $urlparams
     * @return JControllerLegacy
     */
    public function display($cachable = false, $urlparams = array())
    {
        $helper = new YotiHelper;
        $config = YotiHelper::getConfig();

        $redirect = (!empty($_GET['redirect'])) ? $_GET['redirect'] : 'index.php';

        switch ($this->input->get('task')) {
            case 'login':
                try {
                    $userLinked = $helper->link();
                } catch (Exception $ex) {
                    YotiHelper::setFlash('Yoti could not successfully link your account.', 'error');
                }

                if ($userLinked && empty($_GET['redirect'])) {
                    $redirect = $config['yoti_success_url'];
                } elseif (!$userLinked) {
                    // Redirect to failed URL
                    $redirect = ($config['yoti_failed_url'] === '/') ? 'index.php' : $config['yoti_failed_url'];
                }
                // Make sure the custom redirect link is internal
                $redirect = JUri::isInternal($redirect) ? $redirect : 'index.php';
                $this->setRedirect($redirect);
                return;
                break;

            case 'unlink':
                // After unlinking account, redirect to user profile
                $helper->unlink();
                $this->setRedirect('index.php');
                return;
                break;

            case 'bin-file':
                $helper->binFile('selfie');
                exit;
                break;

            default:
                $this->setRedirect($redirect);
                return;
        }

        return parent::display($cachable, $urlparams);
    }
}
