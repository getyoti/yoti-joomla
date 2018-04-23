<?php
/**
 * @license     GNU General Public License version 3; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access');

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
    const YOTI_BUTTON_JS_LIBRARY = 'https://sdk.yoti.com/clients/browser.2.1.0.js';

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
        $this->config = self::getConfig();
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
        $yotiSDKID = $this->config['yoti_sdk_id'];
        $yotiPemContents = $this->config['yoti_pem']->contents;

        $token = (!empty($_GET['token'])) ? $_GET['token'] : NULL;
        $token = YotiHelper::sanitizeToken($token);

        // If no token then ignore
        if (!$token)
        {
            self::setFlash('Could not get Yoti token.', 'error');

            return FALSE;
        }

        // Init Yoti client and attempt to request user details
        try
        {
            $yotiClient = new YotiClient(
                $yotiSDKID,
                $yotiPemContents,
                YotiClient::DEFAULT_CONNECT_API,
                self::SDK_IDENTIFIER
            );
            $activityDetails = $yotiClient->getActivityDetails($token);
        }
        catch (Exception $e)
        {
            self::setFlash('Yoti could not successfully connect to your account.', 'error');

            return FALSE;
        }

        // If unsuccessful then bail
        if (!$this->yotiApiCallIsSuccessfull($yotiClient->getOutcome())) {
            return FALSE;
        }

        if (!$this->passedAgeVerification($activityDetails))
        {
            return FALSE;
        }

        // Check if yoti user exists
        $userId = $this->yotiUserModel->getUserIdByYotiId($activityDetails->getUserId());
        // If Yoti user exists in db but isn't an actual account then remove it from Yoti table
        if (!$currentUser->guest && $userId && $currentUser->id !== $userId)
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
                $errMsg = NULL;

                // Attempt to connect by email.
                $userId = $this->shouldLoginByEmail($activityDetails, $this->config['yoti_user_email']);

                // If config 'only log in existing user' is enabled then check
                // if user exists, if not then redirect to login page.
                if (!$userId) {
                    if (empty($this->config['yoti_only_existing_user'])) {
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
                    return FALSE;
                }
            }

            // Log user in
            $this->loginUser($userId);
        }
        else
        {
            // If logged in user doesn't match yoti user registered then bail
            if ($userId && $currentUser->id !== $userId)
            {
                YotiHelper::setFlash('This Yoti account is already linked to another account.', 'error');
            }
            // If Joomla user not found in Yoti table then create new yoti user
            elseif (!$userId)
            {
                $this->createYotiUser($activityDetails, $currentUser->id);
                YotiHelper::setFlash('Your Yoti account has been successfully linked.');
            }
        }

        return TRUE;
    }

    /**
     * Check if age verification applies and is valid.
     *
     * @param ActivityDetails $activityDetails
     *
     * @return bool
     */
    public function passedAgeVerification(ActivityDetails $activityDetails)
    {
        $ageVerified = $activityDetails->isAgeVerified();
        if ($this->config['yoti_age_verification'] && is_bool($ageVerified) && !$ageVerified)
        {
            $verifiedAge = $activityDetails->getVerifiedAge();
            // If it couldn't create a user then bail
            self::setFlash("Could not log you in as you haven't passed the age verification ({$verifiedAge})", 'error');
            return FALSE;
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
        $filter = '==';
        // Remove anything after ==
        if (!empty($token) && strpos($token, $filter) !== FALSE) {
            $firstToken = strtok($token, $filter);
            if($firstToken !== FALSE) {
                $token = $firstToken . $filter;
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
        if ($outcome !== YotiClient::OUTCOME_SUCCESS)
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
    protected function shouldLoginByEmail(ActivityDetails $activityDetails)
    {
        $userId = 0;
        $email = $activityDetails->getEmailAddress();
        if ($this->config['yoti_user_email']) {
            $joomlaUser = $this->yotiUserModel->getJoomlaUserByEmail($email);
            if ($joomlaUser) {
                $userId = $joomlaUser->get('id');
                $this->createYotiUser($activityDetails, $userId);
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

        // Unlink
        if (!$currentUser->guest && $this->yotiUserModel->deleteYotiUser($currentUser->id))
        {
            self::setFlash('Your Yoti profile is successfully unlinked from your account.');
            YotiHelper::clearYotiUserFromSession();

            return TRUE;
        }

        self::setFlash('Could not unlink your account from Yoti.');

        return FALSE;
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

        $field = ($field === 'selfie') ? 'selfie_filename' : $field;

        $yotiUserData = $this->yotiUserModel->getYotiUserById($user->id);

        $userProfileArr = (!empty($yotiUserData) && isset($yotiUserData['data'])) ? unserialize($yotiUserData['data']) : [];
        if (empty($userProfileArr) || !array_key_exists($field, $userProfileArr))
        {
            return;
        }

        $imagePath = isset($userProfileArr[$field]) ? $userProfileArr[$field] : '';

        $file = YotiHelper::uploadDir() . "/{$imagePath}";
        if (empty($imagePath) || !file_exists($file))
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

        if (NULL !== $givenNames && NULL !== $familyName) {
            $userFullName = $givenNames . ' ' . $familyName;
            $userProvidedPrefix = strtolower(str_replace(' ', '.', $userFullName));
            $prefix = $this->isValidUsername($userProvidedPrefix) ? $userProvidedPrefix : $prefix;
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
            return FALSE;
        }
        return TRUE;
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
        $givenNamesArr = explode(' ', $activityDetails->getGivenNames());
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

        $userFullName = $activityDetails->getGivenNames() . ' ' . $activityDetails->getFamilyName();
        $userFullName = (!empty($userFullName)) ? $userFullName : $defaultUserName;

        $userProvidedEmail = $activityDetails->getEmailAddress();
        // If user has provided an email address and it's not in use then use it,
        // otherwise use Yoti generic email.
        $isValidEmail = $this->isValidEmail($userProvidedEmail);
        $userProvidedEmailCanBeUsed = $isValidEmail && !$this->emailExists($userProvidedEmail);
        $userEmail = $userProvidedEmailCanBeUsed ? $userProvidedEmail : $this->generateEmail();
        // generate fields
        $username = $this->generateUsername($activityDetails);
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
        if (!$user->bind($userData, 'usertype'))
        {
            throw new \Exception('Create user error: ' . $user->getError());
        }
        $user->set('groups', array($newUserType));
        $user->set('registerDate', JFactory::getDate()->toSql());
        if (!$user->save())
        {
            throw new \Exception('Could not save Yoti user');
        }

        // Set new user Id
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

        return $count ? TRUE : FALSE;
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

        // Create Yoti user selfie file
        $selfieFilename = YotiHelper::createUserSelfieFile($activityDetails, $userId);

        // Get Yoti user data array
        $yotiUserData = YotiHelper::getYotiUserData($activityDetails);

        // Replace selfie attribute with the file name attribute
        if(isset($yotiUserData[ActivityDetails::ATTR_SELFIE])) {
            $yotiUserData[self::ATTR_SELFIE_FILE_NAME] = $selfieFilename;
            unset($yotiUserData[ActivityDetails::ATTR_SELFIE]);
        }

        // Format the date of birth to d-m-Y
        if(isset($yotiUserData[ActivityDetails::ATTR_DATE_OF_BIRTH])) {
            $dateOfBirth = $yotiUserData[ActivityDetails::ATTR_DATE_OF_BIRTH];
            $yotiUserData[ActivityDetails::ATTR_DATE_OF_BIRTH] = date('d-m-Y', strtotime($dateOfBirth));
        }

        $user = [
            'joomla_userid' => $userId,
            'identifier' => $activityDetails->getUserId(),
            'data' => serialize($yotiUserData),
        ];

        // Convert into an object and save
        $user = (object) $user;
        $db->insertObject(YotiHelper::YOTI_USER_TABLE_NAME, $user);
    }

    /**
     * Create Yoti user selfie file.
     *
     * @param ActivityDetails $activityDetails
     * @param $userId
     * @return mixed
     */
    protected static function createUserSelfieFile(ActivityDetails $activityDetails, $userId)
    {
        $selfieFilename = NULL;
        $userId = (int) $userId;
        if ($userId && $activityDetails->getProfileAttribute(ActivityDetails::ATTR_SELFIE))
        {
            // Create media dir
            if (!is_dir(self::uploadDir()))
            {
                mkdir(self::uploadDir(), 0777, TRUE);
            }

            $selfieFilename = md5("selfie_$userId" . time()) . '.png';
            file_put_contents(self::uploadDir() . "/$selfieFilename", $activityDetails->getSelfie());
        }

        return $selfieFilename;
    }

    /**
     * Build Yoti user data array.
     *
     * @param ActivityDetails $activityDetails
     * @return array
     */
    protected static function getYotiUserData(ActivityDetails $activityDetails)
    {
        $config = self::getConfig();
        $yotiUserData = [];

        foreach(YotiHelper::$profileFields as $attribute => $label) {
            $yotiUserData[$attribute] = $activityDetails->getProfileAttribute($attribute);
        }

        // Extract age verification values if the option is set in the dashboard
        // and in the Yoti's config in Joomla admin
        $yotiUserData[self::AGE_VERIFICATION_ATTR] = 'N/A';
        $ageVerified = $activityDetails->isAgeVerified();
        if(is_bool($ageVerified) && $config['yoti_age_verification']) {
            $ageVerified = $ageVerified ? 'yes' : 'no';
            $verifiedAge = $activityDetails->getVerifiedAge();
            $yotiUserData[self::AGE_VERIFICATION_ATTR] = "({$verifiedAge}) : $ageVerified";
        }

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
            'remember' => FALSE,
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
        if (empty($config['yoti_app_id']))
        {
            return NULL;
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
        $selfieFileExists = file_exists(self::uploadDir() . $userProfile[self::ATTR_SELFIE_FILE_NAME]);
        if (isset($userProfile[self::ATTR_SELFIE_FILE_NAME]) && $selfieFileExists) {
            // Set Yoti user selfie image in the profile array.
            $userProfile[ActivityDetails::ATTR_SELFIE] = file_get_contents(self::uploadDir() . $userProfile[self::ATTR_SELFIE_FILE_NAME]);
        }
        return new ActivityDetails($userProfile, (int) $userId);
    }
}
