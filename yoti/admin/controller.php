<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
defined('_JEXEC') or die; // No direct access

require_once JPATH_SITE.'/components/com_yoti/YotiHelper.php';

/**
 * Admin controller
 * @author Moussa Sidibe <sdksupport@yoti.com>
 */
class AdminYotiController extends JControllerLegacy
{
    /**
     * @var string
     */
    protected $default_view = 'yoti';
}
