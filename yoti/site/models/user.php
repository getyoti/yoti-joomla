<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_yoti
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once JPATH_ROOT . '/components/com_yoti/YotiHelper.php';

/**
 * User model.
 *
 * @since  1.6
 */
class YotiModelUser extends JModelForm
{

    /**
     * Get the prefix count.
     *
     * @param string $prefix
     *   String to search for.
     * @param string $fieldName
     *   Field to search.
     *
     * @return int $count
     */
    public function getUserPrefixCount($prefix, $fieldName)
    {
        $count = 0;
        if (!empty($prefix) && !empty($fieldName)) {
            $db = JFactory::getDbo();
            $db->setQuery("SELECT COUNT(*) FROM " . $db->quoteName('#__users') .
                " WHERE " . $db->quoteName($fieldName) . " LIKE " .
                $db->quote($prefix. "%"));
            $count = $db->loadResult();
        }
        return $count;
    }

    /**
     * Get query that checks if username exists.
     *
     * @param string $userEmail
     *   User email to search for.
     *
     * @return mixed
     */
    public function getCheckUserEmailExistsQuery($userEmail)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        if (!empty($userEmail)) {
            $query->select('id')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('email') . '=' . $db->quote($userEmail))
                ->setLimit(1);
        }
        return $query;
    }

    /**
     * Get query that checks if username exists.
     *
     * @param string $username
     *   Username to search for.
     *
     * @return mixed
     */
    public function getCheckUsernameExistsQuery($username)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        if (!empty($username)) {
            $query->select('id')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('username') . '=' . $db->quote($username))
                ->setLimit(1);
        }
        return $query;
    }

    /**
     * Get user by email.
     *
     * @param string $email
     *   User email to search for.
     *
     * @return $mixed
     */
    public function getJoomlaUserByEmail($email)
    {
        $user = null;
        if (!empty($email)) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('email') . '=' . $db->quote($email))
                ->setLimit(1);
            $id = $db->setQuery($query)->loadResult();
            if ($id) {
                $user = JFactory::getUser($id);
            }
        }
        return $user;
    }

    /**
     * Check if user email exists.
     *
     * @param string $email
     *   User email.
     *
     * @return bool
     */
    public function emailExists($email)
    {
        $count = 0;
        if (!empty($email)) {
            $db = JFactory::getDbo();
            $query = $this->getCheckUserEmailExistsQuery($email);
            $count  = $db->setQuery($query)->loadResult();
        }

        return $count ? true : false;
    }

    /**
     * Get User Id that is linked to Yoti identifier Id.
     *
     * @param int $yotiId
     *   Yoti user Id.
     *
     * @return int $userId
     */
    public function getUserIdByYotiId($yotiId)
    {
        $userId = 0;
        if ($yotiId) {
            $db = JFactory::getDbo();

            $query = $db->getQuery(true)
                ->select('joomla_userid')
                ->from(YotiHelper::YOTI_USER_TABLE_NAME)
                ->where('identifier=' . $db->quote($yotiId))
                ->setLimit(1);
            $userId = $db->setQuery($query)->loadResult();
        }
        return $userId;
    }

    /**
     * Check if user is linked to Yoti
     *  by checking yoti_users table.
     *
     * @param int $userId
     *
     * @return mixed
     */
    public static function yotiUserIsLinkedToJoomlaUser($userId)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('joomla_userid')
            ->from($db->quoteName(YotiHelper::YOTI_USER_TABLE_NAME))
            ->where($db->quoteName('joomla_userid') . '=' . $db->quote($userId))
            ->setLimit(1);
        return $db->setQuery($query)->loadResult();
    }

    /**
     * Delete Yoti user from the Yoti user table.
     *
     * @param int $userId
     *   Joomla user id
     *
     * @return bool
     */
    public function deleteYotiUser($userId)
    {
        if (!$userId) {
            return false;
        }

        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->delete(YotiHelper::YOTI_USER_TABLE_NAME)
            ->where('joomla_userid=' . $db->quote($userId));
        $db->setQuery($query)->execute();

        return true;
    }

    /**
     * Get Joomla user by Id.
     *
     * @param int $userId
     *   User Id.
     *
     * @return array $joomlaUser
     *   Joomla user data array.
     */
    public function getUserById($userId)
    {
        $joomlaUser = [];

        if ($userId) {
            $db = JFactory::getDbo();

            $query = $db->getQuery(true);
            $query->select('*')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('id') . '=' . $db->quote($userId))
                ->setLimit(1);
            $joomlaUser = $db->setQuery($query)->loadAssoc();
        }

        return $joomlaUser;
    }

    /**
     * Get Yoti user by user Id.
     *
     * @param int $userId
     *   User Id.
     * @return array $userData
     *   Yoti user data array.
     */
    public function getYotiUserById($userId)
    {
        $userData = [];
        if ($userId) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName(YotiHelper::YOTI_USER_TABLE_NAME))
                ->where($db->quoteName('joomla_userid') . '=' . $db->quote($userId))
                ->setLimit(1);
            $userData = $db->setQuery($query)->loadAssoc();
        }

        return $userData;
    }

    /**
     * @param array $data
     * @param bool $loadData
     * @return bool
     */
    public function getForm($data = array(), $loadData = true)
    {
        return false;
    }
}
