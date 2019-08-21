<?php

class YotiAdminCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->ensureJoomlaIsInstalled();
        $I->ensureYotiIsInstalled();
        $I->amLoggedInAsAdmin();
        $I->amOnPage('/administrator/index.php');
    }

    public function configValuesShouldBeTrimmed(AcceptanceTester $I)
    {
        // Browse to configuration form.
        $I->click('Components', '#menu');
        $I->click('Yoti', '#menu');

        // Fill in form with untrimmed test configuration.
        $I->fillField('App ID', " test_app_id\t");
        $I->fillField('Scenario ID', "  test_scenario_id  ");
        $I->fillField('Client SDK ID', " test_sdk_id\n");
        $I->fillField('Company Name', " test_company_name\r");
        $I->fillField('Success URL', " http://www.example.com/success\r");
        $I->fillField('Failed URL', " http://www.example.com/failed\x0B");
        $I->scrollTo('.btn-success');

        $I->click('Save Settings');

        $I->seeElement("input[value='test_app_id']");
        $I->seeElement("input[value='test_scenario_id']");
        $I->seeElement("input[value='test_sdk_id']");
        $I->seeElement("input[value='test_company_name']");
        $I->seeElement("input[value='http://www.example.com/success']");
        $I->seeElement("input[value='http://www.example.com/failed']");
    }
}
