<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ROOT . '/components/com_yoti/ActivityDetailsAdapter.php';

use Yoti\YotiClient;
use Yoti\Entity\Profile;
use YotiJoomla\ActivityDetailsAdapter;
use YotiJoomla\ProfileAdapter;

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
     *
     * @deprecated this is now configured in loader.js
     */
    const YOTI_BUTTON_JS_LIBRARY = 'https://www.yoti.com/share/client/';

    /**
     * Yoti Hub URL.
     */
    const YOTI_HUB_URL = 'https://hub.yoti.com';

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
     * Yoti Joomla SDK identifier.
     */
    const SDK_IDENTIFIER = 'Joomla';

    const AGE_VERIFICATION_ATTR = 'age_verified';

    /**
     * @var YotiModelUser
     *   Yoti User Model.
     */
    public $yotiUserModel;

    /**
     * Admin configuration.
     *
     * @var array
     */
    private $config;

    /**
     * Yoti user profile attributes.
     *
     * @var array
     */
    public static $profileFields = [
        Profile::ATTR_SELFIE => 'Selfie',
        Profile::ATTR_GIVEN_NAMES => 'Given Names',
        Profile::ATTR_FAMILY_NAME => 'Family Name',
        Profile::ATTR_FULL_NAME => 'Full Name',
        Profile::ATTR_PHONE_NUMBER => 'Phone Number',
        Profile::ATTR_DATE_OF_BIRTH => 'Date Of Birth',
        Profile::ATTR_NATIONALITY => 'Nationality',
        Profile::ATTR_GENDER => 'Gender',
        Profile::ATTR_EMAIL_ADDRESS => 'Email Address',
        Profile::ATTR_POSTAL_ADDRESS => 'Postal Address',
    ];

    public function __construct()
    {
        $this->yotiUserModel = new YotiModelUser();
        $this->config = self::getConfig();
    }

    /**
     * Link Yoti user account and log user in.
     *
     * @return bool
     *
     * @throws Exception
     */
    public function link()
    {
        $currentUser = JFactory::getUser();
        $yotiSDKID = $this->config['yoti_sdk_id'];
        $yotiPemContents = $this->config['yoti_pem']->contents;

        $token = (!empty($_GET['token'])) ? $_GET['token'] : null;
        $token = YotiHelper::sanitizeToken($token);

        // If no token then ignore
        if (!$token) {
            self::setFlash('Could not get Yoti token.', 'error');

            return false;
        }

        // Init Yoti client and attempt to request user details
        try {
            $yotiClient = new YotiClient(
                $yotiSDKID,
                $yotiPemContents,
                YotiClient::DEFAULT_CONNECT_API,
                YotiHelper::SDK_IDENTIFIER
            );
            $activityDetails = $yotiClient->getActivityDetails($token);
            $activityDetailsAdapter = new ActivityDetailsAdapter($activityDetails);
            $profileAdapter = $activityDetailsAdapter->getProfile();
        } catch (Exception $e) {
            self::setFlash('Yoti could not successfully connect to your account.', 'error');

            return false;
        }

        if (!$this->passedAgeVerification($profileAdapter)) {
            self::setFlash("Could not log you in as you haven't passed the age verification.", 'error');
            return false;
        }

        // Check if yoti user exists
        $joomlaUserId = $this->yotiUserModel->getUserIdByYotiId($activityDetailsAdapter->getRememberMeId());
        // If Yoti user exists in db but isn't an actual account then remove it from Yoti table
        if (!$currentUser->guest && $joomlaUserId && $currentUser->id !== $joomlaUserId) {
            // Remove users account
            $this->yotiUserModel->deleteYotiUser($joomlaUserId);
        }

        // If user isn't logged in
        if ($currentUser->guest) {
            // Register new user
            if (!$joomlaUserId) {
                $errMsg = null;

                // Attempt to connect by email.
                $joomlaUserId = $this->shouldLoginByEmail($profileAdapter);

                // If config 'only log in existing user' is enabled then check
                // if user exists, if not then redirect to login page.
                if (!$joomlaUserId) {
                    if (empty($this->config['yoti_only_existing_user'])) {
                        try {
                            $joomlaUserId = $this->createJoomlaUser($profileAdapter);
                        } catch (Exception $e) {
                            $errMsg = $e->getMessage();
                        }
                    } else {
                        // Only link existing users account, so redirect to login page
                        self::storeYotiUserInSession($profileAdapter);
                        // Generate the registration path.
                        $userRegistrationURL = JRoute::_('index.php?option=com_users&view=login');
                        JFactory::getApplication()->redirect($userRegistrationURL);
                        return;
                    }
                }

                // No user id? no account
                if (!$joomlaUserId) {
                    // If it couldn't create a user then bail
                    self::setFlash("Could not create user account. $errMsg", 'error');
                    return false;
                }
            }

            // Log user in
            $this->loginUser($joomlaUserId);
        } else {
            // If logged in user doesn't match yoti user registered then bail
            if ($joomlaUserId && ($currentUser->id !== $joomlaUserId)) {
                YotiHelper::setFlash('This Yoti account is already linked to another account.', 'error');
            } elseif (!$joomlaUserId) {
                // If Joomla user not found in Yoti table then create new yoti user
                $this->createYotiUser($profileAdapter, $currentUser->id);
                YotiHelper::setFlash('Your Yoti account has been successfully linked.');
            }
        }

        return true;
    }

    /**
     * Check if age verification applies and is valid.
     *
     * @param ProfileAdapter $profile
     *
     * @return bool
     */
    private function passedAgeVerification(ProfileAdapter $profile)
    {
        return !($this->config['yoti_age_verification'] && !$this->oneAgeIsVerified($profile));
    }

    private function oneAgeIsVerified(ProfileAdapter $profile)
    {
        $ageVerificationsArr = $profile->getAgeVerifications();
        foreach ($ageVerificationsArr as $ageArr) {
            if (in_array('Yes', $ageArr)) {
                return true;
            }
        }
        return false;
    }
    public static function getAgeVerificationsAsString(array $ageVerifications)
    {
        $ageStr = '';
        foreach ($ageVerifications as $ageArr) {
            $ageStr .= key($ageArr) . ': ' . current($ageArr) . ',';
        }
        return rtrim($ageStr, ',');
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
        $filter = '==';
        // Remove anything after ==
        if (!empty($token) && strpos($token, $filter) !== false) {
            $firstToken = strtok($token, $filter);
            if ($firstToken !== false) {
                $token = $firstToken . $filter;
            }
        }
        return trim($token);
    }

    /**
     * Check if we should log user in by email.
     *
     * @param ProfileAdapter $profileAdapter
     * @return int
     *   Joomla User Id
     *
     * @throws \Exception
     */
    protected function shouldLoginByEmail(ProfileAdapter $profileAdapter)
    {
        $userId = 0;
        $email = $profileAdapter->getEmailAddress();
        if ($this->config['yoti_user_email']) {
            $joomlaUser = $this->yotiUserModel->getJoomlaUserByEmail($email);
            if ($joomlaUser) {
                $userId = $joomlaUser->get('id');
                $this->createYotiUser($profileAdapter, $userId);
            }
        }
        return $userId;
    }

    /**
     * Store Yoti user data in the session.
     *
     * @param ProfileAdapter $profileAdapter
     */
    public static function storeYotiUserInSession(ProfileAdapter $profileAdapter)
    {
        $session = JFactory::getSession();

        $session->set('yoti-user', serialize($profileAdapter));
    }

    /**
     * Get Yoti user data from the session.
     *
     * @return ProfileAdapter|null
     */
    public static function getYotiUserFromSession()
    {
        $session = JFactory::getSession();
        $yotiUser = $session->get('yoti-user');
        return $yotiUser ? unserialize($yotiUser) : null;
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

        // Unlink
        if (!$currentUser->guest && $this->yotiUserModel->deleteYotiUser($currentUser->id)) {
            self::setFlash('Your Yoti profile is successfully unlinked from your account.');
            YotiHelper::clearYotiUserFromSession();

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
        if (!$user) {
            return;
        }

        $field = ($field === 'selfie') ? self::ATTR_SELFIE_FILE_NAME : $field;

        $yotiUserData = $this->yotiUserModel->getYotiUserById($user->id);

        $userProfileArr = ($yotiUserData && isset($yotiUserData['data'])) ? unserialize($yotiUserData['data']) : [];

        if (empty($userProfileArr) || !array_key_exists($field, $userProfileArr)) {
            return;
        }

        $imagePath = isset($userProfileArr[$field]) ? $userProfileArr[$field] : '';

        $file = YotiHelper::uploadDir() . "/{$imagePath}";
        if (empty($imagePath) || !file_exists($file)) {
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
     * @param ProfileAdapter $profile
     *
     * @param string $prefix
     *
     * @return string
     */
    private function generateUsername(ProfileAdapter $profile, $prefix = 'yoti.user')
    {
        $db = JFactory::getDbo();
        $givenNames = $this->getUserGivenNames($profile);
        $familyName = $profile->getFamilyName();

        if (null !== $givenNames && null !== $familyName) {
            $userFullName = $givenNames . ' ' . $familyName;
            $userProvidedPrefix = strtolower(str_replace(' ', '.', $userFullName));
            $prefix = $this->isValidUsername($userProvidedPrefix) ? $userProvidedPrefix : $prefix;
        }

        // Generate username
        $username = $prefix;
        $usernameCount = $this->yotiUserModel->getUserPrefixCount($prefix, 'username');
        if ($usernameCount > 0) {
            do {
                $username = $prefix . ++$usernameCount;
                $query = $this->yotiUserModel->getCheckUsernameExistsQuery($username);
                $db->setQuery($query);
            } while ($db->loadResult());
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
        if (!preg_match(self::USERNAME_VALIDATION_PATTERN, $username)) {
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

        // Generate user email
        $userEmail = "{$prefix}@{$domain}";
        if ($userEmailCount > 0) {
            do {
                $userEmail = $prefix . ++$userEmailCount . "@$domain";
                $query = $this->yotiUserModel->getCheckUserEmailExistsQuery($userEmail);
                $db->setQuery($query);
            } while ($db->loadResult());
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
     * @param ProfileAdapter $profile
     *   Yoti user details.
     *
     * @return null|string
     *   Return single user given name
     */
    private function getUserGivenNames(ProfileAdapter $profile)
    {
        $givenNames = $profile->getGivenNames();
        $givenNamesArr = explode(' ', $givenNames);
        return (count($givenNamesArr) > 1) ? $givenNamesArr[0] : $givenNames;
    }

    /**
     * Create Joomla user.
     *
     * @param ProfileAdapter $profileAdapter
     *   Yoti user data
     * @param string $defaultUserName
     *   Default user name.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function createJoomlaUser(ProfileAdapter $profileAdapter, $defaultUserName = 'Yoti User')
    {
        $user = JFactory::getUser(0);
        $usersConfig = JComponentHelper::getParams('com_users');
        $newUserType = $usersConfig->get('new_usertype', 2);

        $userFullName = $profileAdapter->getGivenNames() . ' ' . $profileAdapter->getFamilyName();
        $userFullName = $userFullName ?: $defaultUserName;

        $userProvidedEmail = $profileAdapter->getEmailAddress();
        // If user has provided an email address and it's not in use then use it,
        // otherwise use Yoti generic email.
        $isValidEmail = $this->isValidEmail($userProvidedEmail);
        $userProvidedEmailCanBeUsed = $isValidEmail && !$this->emailExists($userProvidedEmail);
        $userEmail = $userProvidedEmailCanBeUsed ? $userProvidedEmail : $this->generateEmail();
        // generate fields
        $username = $this->generateUsername($profileAdapter);
        $password = $this->generatePassword();

        // Set user data
        $userData = [
            'name' => $userFullName,
            'username' => $username,
            'email' => $userEmail,
            'password' => $password,
            'password2' => $password,
            'sendEmail' => 0,
        ];

        // Save user
        if (!$user->bind($userData, 'usertype')) {
            throw new \Exception('Create user error: ' . $user->getError());
        }
        $user->set('groups', array($newUserType));
        $user->set('registerDate', JFactory::getDate()->toSql());
        if (!$user->save()) {
            throw new \Exception('Could not save Yoti user');
        }

        // Set new user Id
        $joomlaUserId = $user->get('id');
        $this->createYotiUser($profileAdapter, $joomlaUserId);

        return $joomlaUserId;
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

        return $count ? true : false;
    }

    /**
     * Create Yoti user account.
     *
     * @param int $joomlaUserId
     *   Joomla userId.
     *
     * @param ProfileAdapter $profileAdapter
     *   Yoti user data.
     */
    public function createYotiUser(ProfileAdapter $profileAdapter, $joomlaUserId)
    {
        $db = JFactory::getDbo();

        // Create Yoti user selfie file
        $selfieFilename = YotiHelper::createUserSelfieFile($profileAdapter, $joomlaUserId);

        // Get Yoti user data array
        $yotiUserData = YotiHelper::getYotiUserData($profileAdapter);

        // Replace selfie attribute with the file name attribute
        if (isset($yotiUserData[Profile::ATTR_SELFIE])) {
            $selfieAttr = [self::ATTR_SELFIE_FILE_NAME => $selfieFilename];
            $yotiUserData = array_merge(
                $selfieAttr,
                $yotiUserData
            );
            // Remove seflie attr
            unset($yotiUserData[Profile::ATTR_SELFIE]);
        }

        $user = [
            'joomla_userid' => $joomlaUserId,
            'identifier' => $profileAdapter->getYotiUserId(),
            'data' => serialize($yotiUserData),
        ];

        // Convert into an object and save
        $user = (object) $user;
        $db->insertObject(YotiHelper::YOTI_USER_TABLE_NAME, $user);
    }

    /**
     * Create Yoti user selfie file.
     *
     * @param ProfileAdapter $profile
     * @param int $joomlaUserId
     * @return mixed
     */
    protected static function createUserSelfieFile(ProfileAdapter $profile, $joomlaUserId)
    {
        $selfieFilename = null;
        $joomlaUserId = (int) $joomlaUserId;
        $selfie = $profile->getSelfie();
        if ($joomlaUserId && $selfie) {
            // Create media dir
            if (!is_dir(self::uploadDir())) {
                mkdir(self::uploadDir(), 0777, true);
            }

            $selfieFilename = md5("selfie_$joomlaUserId" . time()) . '.png';
            file_put_contents(self::uploadDir() . "/$selfieFilename", $selfie);
        }

        return $selfieFilename;
    }

    /**
     * Build Yoti user data array.
     *
     * @param ProfileAdapter $profile
     *
     * @return array
     */
    protected static function getYotiUserData(ProfileAdapter $profile)
    {
        $yotiUserData = [];
        $attrsArr = array_keys(YotiHelper::$profileFields);
        foreach ($attrsArr as $attrName) {
            if ($attrValue =  $profile->getProfileAttribute($attrName)) {
                $yotiUserData[$attrName] = $attrValue;
            }
        }

        $yotiUserData = array_merge(
            $yotiUserData,
            [Profile::ATTR_AGE_VERIFICATIONS => $profile->getAgeVerifications()]
        );

        return $yotiUserData;
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

        $options = [
            'action' => 'core.login.site',
            'remember' => false,
        ];

        $app->triggerEvent('onUserLogin', [$user, $options]);
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
        if (empty($config['yoti_app_id'])) {
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
     * @return \YotiJoomla\ProfileAdapter
     *   Yoti user profile data.
     */
    public static function makeYotiUserProfile(array $userProfile)
    {
        $userProfile[Profile::ATTR_SELFIE] = null;
        $selfieFileExists = file_exists(self::uploadDir() . $userProfile[self::ATTR_SELFIE_FILE_NAME]);
        if (isset($userProfile[self::ATTR_SELFIE_FILE_NAME]) && $selfieFileExists) {
            // Set Yoti user selfie image in the profile array.
            $filePath = self::uploadDir() . $userProfile[self::ATTR_SELFIE_FILE_NAME];
            $userProfile[Profile::ATTR_SELFIE] = file_get_contents($filePath);
        }

        return new ProfileAdapter($userProfile);
    }

    /**
     * Creates a unique button ID for the current request.
     *
     * @return string
     *   The button ID.
     */
    public static function createButtonId()
    {
        static $x = 0;
        return 'yoti-button-' . ++$x;
    }
}
