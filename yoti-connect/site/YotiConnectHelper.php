<?php
use Yoti\ActivityDetails;
use Yoti\YotiClient;

/**
 * Class YotiConnectHelper
 *
 * @author Simon Tong <simon.tong@yoti.com>
 */
class YotiConnectHelper
{
    /**
     * Running mock requests instead of going to yoti
     * @return bool
     */
    public static function mockRequests()
    {
        return defined('YOTI_MOCK_REQUEST') && YOTI_MOCK_REQUEST;
    }

    /**
     * Login user
     */
    public function link()
    {
        $currentUser = JFactory::getUser();
        $config = self::getConfig();
        $token = (!empty($_GET['token'])) ? $_GET['token'] : null;

        // if no token then ignore
        if (!$token)
        {
            self::setFlash('Could not get Yoti token.', 'error');

            return false;
        }

        // init yoti client and attempt to request user details
        try
        {
            $yotiClient = new YotiClient($config['yoti_sdk_id'], $config['yoti_pem']->contents);
            $yotiClient->setMockRequests(self::mockRequests());
            $activityDetails = $yotiClient->getActivityDetails($token);
        }
        catch (Exception $e)
        {
            self::setFlash('Yoti could not successfully connect to your account.', 'error');

            return false;
        }

        // if unsuccessful then bail
        if ($yotiClient->getOutcome() != YotiClient::OUTCOME_SUCCESS)
        {
            self::setFlash('Yoti could not successfully connect to your account.', 'error');

            return false;
        }

        // check if yoti user exists
        $userId = $this->getUserIdByYotiId($activityDetails->getUserId());

        // if yoti user exists in db but isn't an actual account then remove it from yoti table
        if ($userId && $currentUser->id != $userId && !JFactory::getUser($userId)->id)
        {
            // remove users account
            $this->deleteYotiUser($userId);
        }

        // if user isn't logged in
        if ($currentUser->guest)
        {
            // register new user
            if (!$userId)
            {
                $errMsg = $userId = null;
                try
                {
                    $userId = $this->createUser($activityDetails);
                }
                catch (Exception $e)
                {
                    $errMsg = $e->getMessage();
                }

                // no user id? no account
                if (!$userId)
                {
                    // if couldn't create user then bail
                    self::setFlash("Could not create user account. $errMsg", 'error');

                    return false;
                }
            }

            // log user in
            $this->loginUser($userId);
        }
        else
        {
            // if current logged in user doesn't match yoti user registered then bail
            if ($userId && $currentUser->id != $userId)
            {
                self::setFlash('This Yoti account is already linked to another account.', 'error');
            }
            // if joomla user not found in yoti table then create new yoti user
            elseif (!$userId)
            {
                $this->createYotiUser($currentUser->id, $activityDetails);
                self::setFlash('Your Yoti account has been successfully linked.');
            }
        }

        return true;
    }

    /**
     * Unlink account from currently logged in
     */
    public function unlink()
    {
        $currentUser = JFactory::getUser();

        // unlink
        if (!$currentUser->guest)
        {
            $this->deleteYotiUser($currentUser->id);
            self::setFlash('Your Yoti profile is successfully unlinked from your account.');

            return true;
        }

        self::setFlash('Could not unlink from Yoti.');

        return false;
    }

    /**
     * @param $field
     */
    public function binFile($field)
    {
        $db = JFactory::getDbo();

        $user = JFactory::getUser();
        if (!$user)
        {
            return;
        }

        $field = ($field == 'selfie') ? 'selfie_filename' : $field;
        $qry = $db->getQuery(true)
            ->select('*')
            ->from(self::tableName())
            ->where('joomla_userid=' . $user->id);

        $dbProfile = $db->setQuery($qry)->loadAssoc();
        if (!$dbProfile || !array_key_exists($field, $dbProfile))
        {
            return;
        }

        $file = YotiConnectHelper::uploadDir() . "/{$dbProfile[$field]}";
        if (!file_exists($file))
        {
            return;
        }

        $type = 'image/png';
        header('Content-Type:'.$type);
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    /**
     * @param $message
     * @param string $type
     */
    public static function setFlash($message, $type = 'message')
    {
        $app = JFactory::getApplication();
        $app->enqueueMessage($message, $type);
    }

    /**
     * @return mixed
     */
    public static function getFlash()
    {
        // joomla auto displays messages
    }

    /**
     * @param string $prefix
     * @return string
     */
    private function generateUsername($prefix = 'yoticonnect-')
    {
        $db = JFactory::getDbo();

        // generate username
        $i = 0;
        do
        {
            $username = $prefix . $i++;
            $db->setQuery("SELECT id FROM #__users WHERE username=" . $db->quote($username));
        }
        while ($db->loadResult());

        return $username;
    }

    /**
     * @param $prefix
     * @param string $domain
     * @return string
     */
    private function generateEmail($prefix = 'yoticonnect-', $domain = 'example.com')
    {
        $db = JFactory::getDbo();

        // generate email
        $i = 0;
        do
        {
            $email = $prefix . $i++ . "@$domain";
            $db->setQuery("SELECT id FROM #__users WHERE email=" . $db->quote($email));
        }
        while ($db->loadResult());

        return $email;
    }

    /**
     * @param int $length
     * @return mixed
     */
    private function generatePassword($length = 10)
    {
        return JUserHelper::genRandomPassword($length);
    }

    /**
     * @param ActivityDetails $activityDetails
     * @return int
     * @throws Exception
     */
    private function createUser(ActivityDetails $activityDetails)
    {
        $user = JFactory::getUser(0);
        $usersConfig = JComponentHelper::getParams('com_users');
        $newUserType = $usersConfig->get('new_usertype', 2);

        // generate fields
        $username = $this->generateUsername();
        $email = $this->generateEmail();
        $password = $this->generatePassword();

        // user data
        $userData = array(
            'name' => 'Yoti User',
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'password2' => $password,
            'sendEmail' => 0,
        );

        // save user
        if (!$user->bind($userData, 'usertype'))
        {
            throw new Exception("Create user error: " . $user->getError());
        }
        $user->set('groups', array($newUserType));
        $user->set('registerDate', JFactory::getDate()->toSql());
        if (!$user->save())
        {
            throw new Exception("Could not save Yoti user");
        }

        // set new id
        $userId = $user->get('id');
        $this->createYotiUser($userId, $activityDetails);

        return $userId;
    }

    /**
     * @param $yotiId
     * @return int
     */
    private function getUserIdByYotiId($yotiId)
    {
        $db = JFactory::getDbo();

        $qry = $db->getQuery(true)
            ->select('joomla_userid')
            ->from(self::tableName())
            ->where('identifier=' . $db->quote($yotiId));

        return $db->setQuery($qry)->loadResult();
    }

    /**
     * @param $userId
     * @param ActivityDetails $activityDetails
     */
    private function createYotiUser($userId, ActivityDetails $activityDetails)
    {
        $db = JFactory::getDbo();

        $selfieFilename = null;
        if ($activityDetails->getProfileAttribute(ActivityDetails::ATTR_SELFIE))
        {
            // create media dir
            if (!is_dir(self::uploadDir()))
            {
                mkdir(self::uploadDir(), 0777, true);
            }

            $selfieFilename = "selfie_$userId.png";
            file_put_contents(self::uploadDir() . "/$selfieFilename", $activityDetails->getProfileAttribute(ActivityDetails::ATTR_SELFIE));
        }

        $user = array(
            'joomla_userid' => $userId,
            'identifier' => $activityDetails->getUserId(),
            'date_of_birth' => $activityDetails->getProfileAttribute(ActivityDetails::ATTR_DATE_OF_BIRTH),
            'nationality' => $activityDetails->getProfileAttribute(ActivityDetails::ATTR_NATIONALITY),
            'selfie_filename' => $selfieFilename,
            'phone_number' => $activityDetails->getProfileAttribute(ActivityDetails::ATTR_PHONE_NUMBER),
        );

        $user = (object) $user; // case into object
        $db->insertObject(self::tableName(), $user);
    }

    /**
     * @param int $userId joomla user id
     */
    private function deleteYotiUser($userId)
    {
        $db = JFactory::getDbo();

        $qry = $db->getQuery(true)
            ->delete(self::tableName())
            ->where('joomla_userid=' . $db->quote($userId));
        $db->setQuery($qry)->execute();
    }

    /**
     * @param $userId
     */
    private function loginUser($userId)
    {
        $db = JFactory::getDbo();
        $app = JFactory::getApplication();

        $db->setQuery("SELECT * FROM #__users WHERE id=" . $db->quote($userId));
        $user = $db->loadAssoc();

        $options = array(
            'action' => 'core.login.site',
            'remember' => false,
        );

        $app->triggerEvent('onUserLogin', array($user, $options));
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return '#__yoti_users';
    }

    /**
     * @return string
     */
    public static function uploadDir()
    {
        return JPATH_BASE . '/media/com_yoticonnect';
    }

    /**
     * @return string
     */
    public static function uploadUrl()
    {
        global $baseurl;

        return "$baseurl/media/com_yoticonnect";
    }

    /**
     * @return array
     */
    public static function getConfig()
    {
        if (self::mockRequests())
        {
            $config = require_once JPATH_BASE.'/components/com_yoticonnect/sdk/sample-data/config.php';
            $config['yoti_pem'] = (object)$config['yoti_pem'];
            return $config;
        }

        return JComponentHelper::getParams('com_yoticonnect');
    }

    /**
     * @return null|string
     */
    public static function getLoginUrl()
    {
        $config = self::getConfig();
        if (empty($config['yoti_app_id']))
        {
            return null;
        }

        return YotiClient::getLoginUrl($config['yoti_app_id']);
    }
}
