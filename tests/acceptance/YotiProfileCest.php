<?php

class YotiProfileCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->ensureJoomlaIsInstalled();
        $I->ensureYotiIsInstalled();
        $I->amLoggedInAsLinkedUser();
    }

    public function profileFields(AcceptanceTester $I)
    {
        $I->amOnPage('index.php?option=com_users&view=profile');
        $I->see('Yoti User Profile');
        $I->see('family_name test value');
        $I->see('given_names test value');
        $I->see('full_name test value');
        $I->see('phone_number test value');
        $I->see('nationality test value');
        $I->see('gender test value');
        $I->see('email_address test value');
        $I->see('postal_address test value');
        $I->see('date_of_birth test value');
    }
}
