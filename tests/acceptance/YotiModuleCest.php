<?php

class YotiModuleCest
{
    /**
     * @var boolean Flag to prevent Joomla being installed twice.
     */
    private $joomlaInstalled = false;

    public function _before(AcceptanceTester $I) {
        if (!$this->joomlaInstalled) {
            $I->installJoomlaRemovingInstallationFolder();
            $I->doAdministratorLogin();
            $I->closeMessages();
            $I->installExtensionFromFolder('/var/www/html/yoti-joomla');
            $I->configureTheYotiComponent();
            $this->joomlaInstalled = true;
        }
        else {
            $I->doAdministratorLogin();
        }
    }

    public function placeLoginModule(AcceptanceTester $I)
    {
        $I->placeTheYotiModule();
        $I->amOnPage('/');
        $I->waitForElement('a[data-scenario-id="test_scenario_id"][data-application-id="test_app_id"]');
        $I->see('Use Yoti');
    }
}
