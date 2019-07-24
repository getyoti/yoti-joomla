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

        $config = array(
            'domId' => 'yoti-button-1',
            'clientSdkId' => 'test_sdk_id',
            'scenarioId' => 'test_scenario_id',
            'button' => array(
                'label' => 'Use Yoti',
            ),
        );
        $I->canSeeInSource(json_encode($config));
        $I->waitForElement('#yoti-button-1 iframe');

        $I->doFrontEndLogin();
        $I->amOnPage('/');

        $config['button']['label'] = 'Link to Yoti';
        $I->canSeeInSource(json_encode($config));
        $I->waitForElement('#yoti-button-1 iframe');
    }
}
