<?php

class YotiModuleCest
{
    public function placeModule(AcceptanceTester $I)
    {
        $I->doAdministratorLogin();
        $I->click('Components', '#menu');
        $I->click('Yoti', '#menu');
        $I->fillField('App ID', 'test_app_id');
        $I->fillField('Scenario ID', 'test_scenario_id');
        $I->fillField('SDK ID', 'test_sdk_id');
        $I->fillField('Company Name', 'test_company_name');
        $I->attachFile('input[type="file"][id="yoti_pem"]', 'test.pem');
        $I->scrollTo('.btn-success');
        $I->click('Save Settings');

        $I->click('Extensions', '#menu');
        $I->click('Modules', '#menu');
        $I->scrollTo('#moduleList');
        $I->click('Yoti Login', '#moduleList .pull-left');

        $I->see('Yoti Login');

        $I->waitForElement('#jform_position_chzn');

        $I->click('#jform_position_chzn');
        $I->click('#jform_position_chzn [data-option-array-index="28"]');
        $I->click('#jform_published_chzn');
        $I->click('#jform_published_chzn [data-option-array-index="0"]');
        $I->fillField('Finish Publishing', '');
        $I->click('Save');

        $I->click('Menu Assignment');
        $I->click('#jform_assignment_chzn');
        $I->click('#jform_assignment_chzn [data-option-array-index="0"]');
        $I->click('Save');

        // $I->amOnPage('/');
        // $I->waitForElement('a[data-scenario-id="test_scenario_id"]');
        // $I->see('a[data-scenario-id="test_scenario_id"][data-application-id="test_app_id"]');
    }
}
