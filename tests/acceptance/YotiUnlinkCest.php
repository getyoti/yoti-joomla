<?php

class YotiUnlinkCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->ensureJoomlaIsInstalled();
        $I->ensureYotiIsInstalled();
        $I->amLoggedInAsLinkedUser();
    }

    public function linkedModuleMessage(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Yoti Linked');
    }

    public function unlinkAccount(AcceptanceTester $I)
    {
        $I->amOnPage('index.php?option=com_users&view=profile');
        $I->click('Unlink Yoti Account');
        $I->acceptPopup();
        $I->see('Your Yoti profile is successfully unlinked from your account.');
    }

    public function unlinkAccountFailure($I)
    {
        $I->amOnPage('index.php?option=com_yoti&task=unlink');
        $I->see('Yoti could not successfully unlink your account.');
    }
}
