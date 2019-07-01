<?php

class YotiModuleCest
{
    public function placeModule(AcceptanceTester $I)
    {
        $I->doAdministratorLogin();
        $I->configureTheYotiComponent();
        $I->placeTheYotiModule();

        $I->amOnPage('/');
        $I->waitForElement('a[data-scenario-id="test_scenario_id"][data-application-id="test_app_id"]');
        $I->see('Use Yoti');
    }
}
