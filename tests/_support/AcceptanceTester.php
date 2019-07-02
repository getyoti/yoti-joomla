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

        // Browse to Yoti module.
        $I->click('Extensions', '#menu');
        $I->click('Modules', '#menu');
        $I->scrollTo('#moduleList');
        $I->click('Yoti Login', '#moduleList .pull-left');

        // Set position.
        $I->waitForElement('#jform_position_chzn');
        $I->click('#jform_position_chzn');
        $I->click('#jform_position_chzn [data-option-array-index="28"]');

        // Publish.
        $I->click('#jform_published_chzn');
        $I->click('#jform_published_chzn [data-option-array-index="0"]');

        // Clear publish end date.
        $I->fillField('Finish Publishing', '');

        $I->click('Save');

        // Browser to menu assignment.
        $I->click('Menu Assignment');

        // Place on every page.
        $I->click('#jform_assignment_chzn');
        $I->click('#jform_assignment_chzn [data-option-array-index="0"]');

        $I->click('Save');
    }
}
