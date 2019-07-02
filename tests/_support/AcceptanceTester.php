<?php


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
    public function configureTheYotiComponent() {
        $I = $this;

        // Browse to configuration form.
        $I->click('Components', '#menu');
        $I->click('Yoti', '#menu');

        // Fill in form with test configuration.
        $I->fillField('App ID', 'test_app_id');
        $I->fillField('Scenario ID', 'test_scenario_id');
        $I->fillField('SDK ID', 'test_sdk_id');
        $I->fillField('Company Name', 'test_company_name');
        $I->attachFile('input[type="file"][id="yoti_pem"]', 'test.pem');
        $I->scrollTo('.btn-success');

        $I->click('Save Settings');
    }

    /**
     * Ensure Joomla is installed.
     */
    public function ensureJoomlaIsInstalled() {
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
    public function ensureYotiIsInstalled() {
        $I = $this;
        $I->amLoggedInAsAdmin();
        $I->amOnPage('/administrator/index.php?option=com_modules');
        if (!$I->findElements(['link' => 'Yoti Login'])) {
            $I->installExtensionFromFolder('/var/www/html/yoti-joomla');
            $I->configureTheYotiComponent();
        }
    }

    /**
     * Ensure logged in as administrator.
     */
    public function amLoggedInAsAdmin() {
        $I = $this;
        $I->amOnPage('/administrator/index.php');
        if ($I->findElements('#mod-login-username')) {
            $I->doAdministratorLogin();
        }
    }

    /**
     * Close/accept banner messages.
     */
    public function closeMessages() {
        $I = $this;
        $I->waitForElement('.js-pstats-btn-allow-never');
        $I->click('Never');
    }

    /**
     * Places the Yoti Module.
     */
    public function placeTheYotiModule() {
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
}
