<?php

class YotiModuleCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->ensureJoomlaIsInstalled();
        $I->ensureYotiIsInstalled();
    }

    public function placeLoginModule(AcceptanceTester $I)
    {
        $I->amLoggedInAsAdmin();
        $I->placeTheYotiModule();
        $I->amOnPage('/');
        $I->waitForElement('a[data-scenario-id="test_scenario_id"][data-application-id="test_app_id"]');
        $I->see('Use Yoti');
    }
}
