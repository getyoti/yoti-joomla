<?php

use Yoti\ActivityDetails;
use Yoti\YotiClient;
//Load the Joomla Model framework
jimport('joomla.application.component.model');
// Load YotiUserModel
JLoader::register('YotiModelUser', JPATH_ROOT . '/components/com_yoti/models/user.php');


/**
 * Class YotiHelper
 *
 * @author Moussa Sidibe <moussa.sidibe@yoti.com>
 */
class YotiHelper
{
    /**
     * Yoti user database table name.
     */
    const YOTI_USER_TABLE_NAME = '#__yoti_users';

    /**
     * Yoti link button default text.
     */
    const YOTI_LINK_BUTTON_DEFAULT_TEXT = 'Use Yoti';

    /**
     * Yoti Button javascript library.
     */
    const YOTI_BUTTON_JS_LIBRARY = 'https://sdk.yoti.com/clients/browser.js';

    /**
     * Yoti Button version, leave it blank for version 1.
     */
    const YOTI_BUTTON_JS_LIBRARY_VERSION = '';

    /**
     * Yoti files upload dir
     */
    const YOTI_MEDIA_UPLOAD_DIR = '/media/com_yoti';

    /**
     * Yoti selfie filename attribute.
     */
    const ATTR_SELFIE_FILE_NAME = 'selfie_filename';

    /**
     * Rule to validate username
     *  should be between 3 and 32 characters
     *  should start with a character
     *  can include ._-
     */
    const USERNAME_VALIDATION_PATTERN = '/^[A-z0-9\._-]{3,32}$/i';

    /**
     * @var YotiModelUser
     *   Yoti User Model.
     */
    public $yotiUserModel;

    /**
     * Yoti user profile attributes.
     *
     * @var array
     */
    public static $profileFields = [
        ActivityDetails::ATTR_SELFIE => 'Selfie',
        ActivityDetails::ATTR_PHONE_NUMBER => 'Phone number',
        ActivityDetails::ATTR_DATE_OF_BIRTH => 'Date of birth',
        ActivityDetails::ATTR_GIVEN_NAMES => 'Given names',
        ActivityDetails::ATTR_FAMILY_NAME => 'Family name',
        ActivityDetails::ATTR_NATIONALITY => 'Nationality',
        ActivityDetails::ATTR_GENDER => 'Gender',
        ActivityDetails::ATTR_EMAIL_ADDRESS => 'Email Address',
        ActivityDetails::ATTR_POSTAL_ADDRESS => 'Postal Address',
    ];

    public function __construct()
    {
        $this->yotiUserModel = new YotiModelUser();
    }

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
    /**
     * Link Yoti user account and log user in.
     * @return bool
     */
    public function link()
    {
        $currentUser = JFactory::getUser();
        $config = self::getConfig();
        $yotiSDKID = $config['yoti_sdk_id'];
        $yotiPemContents = $config['yoti_pem']->contents;

        $token = (!empty($_GET['token'])) ? $_GET['token'] : null;
        $token = YotiHelper::sanitizeToken($token);

        // If no token then ignore
        if (!$token)
        {
            self::setFlash('Could not get Yoti token.', 'error');

            return false;
        }

        // Init Yoti client and attempt to request user details
        try
        {
            $yotiClient = new YotiClient($yotiSDKID, $yotiPemContents);
            $yotiClient->setMockRequests(self::mockRequests());
            $activityDetails = $yotiClient->getActivityDetails($token);
        }
        catch (Exception $e)
        {
            self::setFlash('Yoti could not successfully connect to your account.', 'error');

            return false;
        }

        // If unsuccessful then bail
        if (!$this->yotiApiCallIsSuccessfull($yotiClient->getOutcome())) {
            return false;
        }

        // Check if yoti user exists
        $userId = $this->yotiUserModel->getUserIdByYotiId($activityDetails->getUserId());

        // If Yoti user exists in db but isn't an actual account then remove it from yoti table
        if (!$currentUser->guest && $userId && $currentUser->id != $userId)
        {
            // Remove users account
            $this->yotiUserModel->deleteYotiUser($userId);
        }

        // If user isn't logged in
        if ($currentUser->guest)
        {
            // Register new user
            if (!$userId)
            {
                $errMsg = null;

                // Attempt to connect by email.
                $userId = $this->loginByEmail($activityDetails);

                // If config 'only log in existing user' is enabled then check
                // if user exists, if not then redirect to login page.
                if (!$userId) {
                    if (empty($config['yoti_only_existing_user'])) {
                        try {
                            $userId = $this->createUser($activityDetails);
                        }
                        catch (Exception $e) {
                            $errMsg = $e->getMessage();
                        }
                    }
                    else {
                        // Only link existing users account, so redirect to login page
                        self::storeYotiUserInSession($activityDetails);
                        // Generate the registration path.
                        $userRegistrationURL = JRoute::_('index.php?option=com_users&view=login');
                        JFactory::getApplication()->redirect($userRegistrationURL);
                        return;
                    }
                }

                // No user id? no account
                if (!$userId)
                {
                    // If it couldn't create a user then bail
                    self::setFlash("Could not create user account. $errMsg", 'error');

                    return false;
                }
            }

            // Log user in
            $this->loginUser($userId);
        }
        else
        {
            // If logged in user doesn't match yoti user registered then bail
            if ($userId && $currentUser->id != $userId)
            {
                self::setFlash('This Yoti account is already linked to another account.', 'error');
            }
            // If Joomla user not found in Yoti table then create new yoti user
            elseif (!$userId)
            {
                $this->createYotiUser($activityDetails, $currentUser->id);
                self::setFlash('Your Yoti account has been successfully linked.');
            }
        }

        return TRUE;
    }

    /**
     * Remove query params from the end of the token.
     *
     * @param string $token
     *   Token to clean up
     *
     * @return string
     *   Clean token
     */
    public static function sanitizeToken($token)
    {
        $delimitor = "==";
        //Remove anything after ==
        if (!empty($token) && ($pos = strpos($token, $delimitor)) !== FALSE) {
            $firstToken = strtok($token, $delimitor);
            if($firstToken !== FALSE) {
                $token = $firstToken . $delimitor;
            }
        }
        return trim($token);
    }

    /**
     * Check if call to Yoti API has been successful.
     *
     * @param string $outcome
     *
     * @return bool
     *   Returns TRUE of FALSE
     */
    protected function yotiApiCallIsSuccessfull($outcome)
    {
        // If unsuccessful then bail
        if ($outcome != YotiClient::OUTCOME_SUCCESS)
        {
            self::setFlash('Yoti could not successfully connect to your account.', 'error');

            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check if we should log user in by email.
     *
     * @param ActivityDetails $activityDetails
     *   Yoti user data.
     *
     * @return int userId
     *  Joomla userId.
     */
    protected function loginByEmail(ActivityDetails $activityDetails)
    {
        $userId = 0;
        $config = self::getConfig();
        $config = ($config instanceof Joomla\Registry\Registry) ? $config->toArray() : $config;
        if (isset($config['yoti_user_email']) && !empty($config['yoti_user_email'])) {
            if (($email = $activityDetails->getEmailAddress())) {
                $joomlaUser = $this->yotiUserModel->getJoomlaUserByEmail($email);
                if ($joomlaUser) {
                    $userId = $joomlaUser->get('id');
                    $this->createYotiUser($activityDetails, $userId);
                }
            }
        }

        return $userId;
    }

    /**
     * Store Yoti user data in the session.
     *
     * @param ActivityDetails $activityDetails
     */
    public static function storeYotiUserInSession(ActivityDetails $activityDetails)
    {
        $session = JFactory::getSession();
        $session->set('yoti-user', serialize($activityDetails));
    }

    /**
     * Get Yoti user data from the session.
     *
     * @return mixed|null
     */
    public static function getYotiUserFromSession()
    {
        $session = JFactory::getSession();
        $yotiUser = $session->get('yoti-user');
        $activityDetails = (!empty($yotiUser)) ? unserialize($yotiUser) : NULL;
        return $activityDetails;
    }

    /**
     * Remove Yoti user data from the session.
     */
    public static function clearYotiUserFromSession()
    {
        $session = JFactory::getSession();
        $session->clear('yoti-user');
    }

    /**
     * Unlink Yoti user account from Joomla user.
     * @return bool
     */
    public function unlink()
    {
        $currentUser = JFactory::getUser();

        // unlink
        if (!$currentUser->guest)
        {
            $this->yotiUserModel->deleteYotiUser($currentUser->id);
            self::setFlash('Your Yoti profile is successfully unlinked from your account.');

            return true;
        }

        self::setFlash('Could not unlink your account from Yoti.');

        return false;
    }

    /**
     * Output user image.
     *
     * @param string $field
     *   Field that holds image path.
     */
    public function binFile($field)
    {
        $user = JFactory::getUser();
        if (!$user)
        {
            return;
        }

        $field = ($field == 'selfie') ? 'selfie_filename' : $field;

        $yotiUserData = $this->yotiUserModel->getYotiUserById($user->id);
        $userProfileArr = (!empty($yotiUserData) && isset($yotiUserData['data'])) ? unserialize($yotiUserData['data']) : [];
        if (empty($userProfileArr) || !array_key_exists($field, $userProfileArr))
        {
            return;
        }

        $file = YotiHelper::uploadDir() . "/{$userProfileArr[$field]}";
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
     * Set Joomla notification message.
     *
     * @param string $message
     * @param string $type
     */
    public static function setFlash($message, $type = 'message')
    {
        $app = JFactory::getApplication();
        $app->enqueueMessage($message, $type);
    }

    /**
     * Get Joomla notification message.
     *
     * @return mixed
     */
    public static function getFlash()
    {
        // Return Joomla notification message
        $app = JFactory::getApplication();

        return $app->getMessageQueue();
    }

    /**
     * Generate username.
     *
     * @param string $prefix
     *   Username prefix.
     *
     * @return string
     */
    private function generateUsername(ActivityDetails $activityDetails, $prefix = 'yoti.user')
    {
        $db = JFactory::getDbo();
        $givenNames = $this->getUserGivenNames($activityDetails);
        $familyName = $activityDetails->getFamilyName();

        if (!empty($givenNames) && !empty($familyName)) {
            $userFullName = $givenNames . " " . $familyName;
            $userProvidedPrefix = strtolower(str_replace(" ", ".", $userFullName));
            $prefix = ($this->isValidUsername($userProvidedPrefix)) ? $userProvidedPrefix : $prefix;
        }

        // Generate username
        $username = $prefix;
        $usernameCount = $this->yotiUserModel->getUserPrefixCount($prefix, 'username');
        if ($usernameCount > 0) {
            do
            {
                $username = $prefix . ++$usernameCount;
                $query = $this->yotiUserModel->getCheckUsernameExistsQuery($username);
                $db->setQuery($query);
            }
            while ($db->loadResult());
        }

        return $username;
    }

    /**
     * Check a username has valid characters.
     * Rules
     *   Must start with letter
     *   6-32 characters
     *   Letters and numbers only
     *
     * @param string $username
     *   Username to be validated.
     *
     * @return bool|int
     *   Return TRUE or FALSE
     */
    protected function isValidUsername($username)
    {
        if(!preg_match(self::USERNAME_VALIDATION_PATTERN, $username)) {
            return false;
        }
        return true;
    }

    /**
     * @param $prefix
     * @param string $domain
     * @return string
     */
    private function generateEmail($prefix = 'yoti.user', $domain = 'example.com')
    {
        $db = JFactory::getDbo();
        $userEmailCount = $this->yotiUserModel->getUserPrefixCount($prefix, 'email');

        // generate email
        $userEmail = "{$prefix}@{$domain}";
        if ($userEmailCount > 0) {
            do
            {
                $userEmail = $prefix . ++$userEmailCount . "@$domain";
                $query = $this->yotiUserModel->getCheckUserEmailExistsQuery($userEmail);
                $db->setQuery($query);
            }
            while ($db->loadResult());
        }
        return $userEmail;
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
     * If user has more than one given name return the first one.
     *
     * @param \Yoti\ActivityDetails $activityDetails
     *   Yoti user details.
     *
     * @return null|string
     *   Return single user given name
     */
    private function getUserGivenNames(ActivityDetails $activityDetails) {
        $givenNames = $activityDetails->getGivenNames();
        $givenNamesArr = explode(" ", $activityDetails->getGivenNames());
        return (count($givenNamesArr) > 1) ? $givenNamesArr[0] : $givenNames;
    }

    /**
     * Create Joomla user.
     *
     * @param ActivityDetails $activityDetails
     *   Yoti user data
     * @param string $defaultUserName
     *   Default user name.
     * @return mixed
     *
     * @throws Exception
     */
    private function createUser(ActivityDetails $activityDetails, $defaultUserName = 'Yoti User')
    {
        $user = JFactory::getUser(0);
        $usersConfig = JComponentHelper::getParams('com_users');
        $newUserType = $usersConfig->get('new_usertype', 2);

        $userFullName = $activityDetails->getGivenNames() . " " . $activityDetails->getFamilyName();
        $userFullName = (!empty($userFullName)) ? $userFullName : $defaultUserName;

        $userProvidedEmail = $activityDetails->getEmailAddress();
        // If user has provided an email address and it's not in use then use it,
        // otherwise use Yoti generic email.
        $isValidEmail = $this->isValidEmail($userProvidedEmail);
        $userProvidedEmailCanBeUsed = $isValidEmail && !$this->emailExists($userProvidedEmail);
        $userEmail = ($userProvidedEmailCanBeUsed) ? $userProvidedEmail : $this->generateEmail();
        // generate fields
        $username = $this->generateUsername($activityDetails);
        $password = $this->generatePassword();

        // user data
        $userData = [
            'name' => $userFullName,
            'username' => $username,
            'email' => $userEmail,
            'password' => $password,
            'password2' => $password,
            'sendEmail' => 0,
        ];

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
        $this->createYotiUser($activityDetails, $userId);

        return $userId;
    }

    /**
     * Check user email is valid.
     *
     * @param string $email
     *
     * @return bool
     */
    public function isValidEmail($email)
    {
        return (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL));
    }

    /**
     * Check email exists.
     *
     * @param string $email
     *
     * @return bool
     */
    public function emailExists($email)
    {
        $count = 0;
        if (!empty($email)) {
            $db = JFactory::getDbo();
            $query = $this->yotiUserModel->getCheckUserEmailExistsQuery($email);
            $count  = $db->setQuery($query)->loadResult();
        }

        return ($count) ? true : false;
    }

    /**
     * Check if Yoti user is linked to Joomla user.
     *
     * @param int $userId
     *   Joomla userId
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
            ->setLimit('1');
        return ($db->setQuery($query)->loadResult());
    }

    /**
     * Create Yoti user account.
     *
     * @param int $userId
     *   Joomla userId.
     *
     * @param ActivityDetails $activityDetails
     *   Yoti user data.
     */
    public function createYotiUser(ActivityDetails $activityDetails, $userId)
    {
        $db = JFactory::getDbo();

        $selfieFilename = null;
        if ($activityDetails->getProfileAttribute(ActivityDetails::ATTR_SELFIE))
        {
            // Create media dir
            if (!is_dir(self::uploadDir()))
            {
                mkdir(self::uploadDir(), 0777, true);
            }

            $selfieFilename = "selfie_$userId.png";
            file_put_contents(self::uploadDir() . "/$selfieFilename", $activityDetails->getSelfie());
        }

        $yotiUserData = [];
        foreach(YotiHelper::$profileFields as $attribute => $label) {
            if ($attribute == ActivityDetails::ATTR_SELFIE) {
                $yotiUserData[self::ATTR_SELFIE_FILE_NAME] = $selfieFilename;
            } else {
                $yotiUserData[$attribute] = $activityDetails->getProfileAttribute($attribute);
            }
        }

        $user = [
            'joomla_userid' => $userId,
            'identifier' => $activityDetails->getUserId(),
            'data' => serialize($yotiUserData),
        ];

        // Convert into an object
        $user = (object) $user;
        $db->insertObject(YotiHelper::YOTI_USER_TABLE_NAME, $user);
    }

    /**
     * Log user in.
     *
     * @param int $userId
     *   Joomla user Id.
     */
    private function loginUser($userId)
    {
        $app = JFactory::getApplication();

        $user = $this->yotiUserModel->getUserById($userId);

        $options = array(
            'action' => 'core.login.site',
            'remember' => false,
        );

        $app->triggerEvent('onUserLogin', array($user, $options));
    }

    /**
     * Yoti files upload dir.
     *
     * @return string
     */
    public static function uploadDir()
    {
        return JPATH_BASE . YotiHelper::YOTI_MEDIA_UPLOAD_DIR;
    }

    /**
     * Yoti files upload URL.
     *
     * @return string
     */
    public static function uploadUrl()
    {
        global $baseurl;

        return "$baseurl" . YotiHelper::YOTI_MEDIA_UPLOAD_DIR;
    }

    /**
     * Yoti config data.
     *
     * @return array
     */
    public static function getConfig()
    {
        if (self::mockRequests())
        {
            $config = require_once JPATH_BASE.'/components/com_yoti/sdk/sample-data/config.php';
            $config['yoti_pem'] = (object)$config['yoti_pem'];

            return $config;
        }

        $config =  JComponentHelper::getParams('com_yoti');

        return $config;
    }

    /**
     * Yoti connect login URL.
     *
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

    /**
     * Make Yoti user prfoile object.
     *
     * @param array $userProfile
     *   Yoti user profile array.
     * @param int $userId
     *   Yoti user ID.
     *
     * @return \Yoti\ActivityDetails
     *   Yoti user profile data.
     */
    public static function makeYotiUserProfile(array $userProfile, $userId)
    {
        $userProfile[ActivityDetails::ATTR_SELFIE] = NULL;
        if (isset($userProfile[self::ATTR_SELFIE_FILE_NAME])) {
            // Set Yoti user selfie image in the profile array.
            if (file_exists(self::uploadDir() . $userProfile[self::ATTR_SELFIE_FILE_NAME])) {
                $userProfile[ActivityDetails::ATTR_SELFIE] = file_get_contents(self::uploadDir() . $userProfile[self::ATTR_SELFIE_FILE_NAME]);
            }
        }
        return new ActivityDetails($userProfile, (int) $userId);
    }
}
