<?php
use YotiJoomla\ProfileAdapter;
use Yoti\Entity\Profile;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Configures the Yoti Component.
     */
    public function configureTheYotiComponent()
    {
        $I = $this;

        // Browse to configuration form.
        $I->click('Components', '#menu');
        $I->click('Yoti', '#menu');

        // Fill in form with test configuration.
        $I->fillField('App ID', 'test_app_id');
        $I->fillField('Scenario ID', 'test_scenario_id');
        $I->fillField('Client SDK ID', 'test_sdk_id');
        $I->fillField('Company Name', 'test_company_name');
        $I->attachFile('input[type="file"][id="yoti_pem"]', 'test.pem');
        $I->scrollTo('.btn-success');

        $I->click('Save Settings');
    }

    /**
     * Ensure Joomla is installed.
     */
    public function ensureJoomlaIsInstalled()
    {
        $I = $this;
        $I->amOnPage('/');
        if (!$I->findElements('.site-title')) {
            $I->installJoomlaRemovingInstallationFolder();
            $I->amLoggedInAsAdmin();
            $I->closeMessages();
        }
    }

    /**
     * Ensure Yoti module is installed.
     */
    public function ensureYotiIsInstalled()
    {
        $I = $this;
        $I->amLoggedInAsAdmin();
        $I->amOnPage('/administrator/index.php?option=com_modules');
        if (!$I->findElements(['link' => 'Yoti Login'])) {
            $I->installExtensionFromFolder($this->getJoomlaFolder() . 'yoti');
            $I->configureTheYotiComponent();
            $I->enablePlugin('Yoti');
        }
    }

    /**
     * Ensure logged in as administrator.
     */
    public function amLoggedInAsAdmin()
    {
        $I = $this;
        $I->amOnPage('/administrator/index.php');
        if ($I->findElements('#mod-login-username')) {
            $I->doAdministratorLogin();
        }
    }

    /**
     * Close/accept banner messages.
     */
    public function closeMessages()
    {
        $I = $this;
        $I->waitForElement('.js-pstats-btn-allow-never');
        // Wait for slide transition to finish.
        $I->wait(2);
        $I->click('Never');
    }

    /**
     * Places the Yoti Module.
     */
    public function placeTheYotiModule()
    {
        $I = $this;
        $I->amLoggedInAsAdmin();
        $I->click('Extensions', '#menu');
        $I->click('Modules', '#menu');
        $I->click('Yoti Login');
        $I->selectOptionInChosen('Position', 'position-7');
        $I->selectOptionInChosen('Status', 'Published');
        $I->fillField('Finish Publishing', '');
        $I->click('Save');
        $I->click('Menu Assignment');
        $I->selectOptionInChosenByIdUsingJs('jform_assignment', 'On all pages');
        $I->click('Save');
    }

    /**
     * Creates a Joomla user.
     *
     * User with same username will be replaced.
     *
     * @param string $username
     */
    private function createJoomlaUser($username)
    {
        // Remove user if they already exist.
        $db = JFactory::getDbo();
        $model = new YotiModelUser();
        $query = $model->getCheckUsernameExistsQuery('linked_user');
        $db->setQuery($query);
        if ($userId = $db->loadResult()) {
            JFactory::getUser($userId)->delete();
        }

        // Get new user type.
        $user = JFactory::getUser(0);
        $usersConfig = JComponentHelper::getParams('com_users');
        $newUserType = $usersConfig->get('new_usertype', 2);

        // Set user data
        $userData = [
            'name' => $username,
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => $username,
            'password2' => $username,
            'sendEmail' => 0,
        ];

        // Save user.
        if (!$user->bind($userData, 'usertype')) {
            throw new \Exception('Create user error: ' . $user->getError());
        }
        $user->set('groups', array($newUserType));
        $user->set('registerDate', JFactory::getDate()->toSql());
        if (!$user->save()) {
            throw new \Exception('Could not save Yoti user');
        }

        // Return new user Id.
        return $user->get('id');
    }

    /**
     * Log in as a linked user.
     */
    public function amLoggedInAsLinkedUser()
    {
        $this->bootstrapJoomla();

        $userId = $this->createJoomlaUser('linked_user');

        // Generate test attributes.
        $attributes = array_flip([
            Profile::ATTR_FAMILY_NAME,
            Profile::ATTR_GIVEN_NAMES,
            Profile::ATTR_FULL_NAME,
            Profile::ATTR_DATE_OF_BIRTH,
            Profile::ATTR_GENDER,
            Profile::ATTR_NATIONALITY,
            Profile::ATTR_PHONE_NUMBER,
            Profile::ATTR_EMAIL_ADDRESS,
            Profile::ATTR_POSTAL_ADDRESS,
        ]);
        array_walk($attributes, function (&$item, $key) {
            $item = $key . ' test value';
        });
        $attributes['yoti_user_id'] = 'some_remember_me_id';
        $profileAdapter = new ProfileAdapter($attributes);

        $helper = new YotiHelper();
        $helper->createYotiUser($profileAdapter, $userId);

        $this->doFrontEndLogin('linked_user', 'linked_user');
    }
}
