<?php


namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class SecondCest
{
    public function Login(AcceptanceTester $I)
    {
        $I->amOnPage('/home/home.php');
        $I->click('Login');
        $I->seeInCurrentUrl('/login/login.html');
        $I->fillField('Nickname', 'gaetest'); 
        $I->fillField('Password', 'password');
        $I->click('Confirm');
        $I->seeInCurrentUrl('/home/home.php');
        $I->click(['id' => 'myProfileLink']);
        $I->seeInCurrentUrl('/memberPage/myProfile.php');
        $I->seeElement('input[name="email"][value="gaetest@example.com"]');
        
    }

    // tests
    //public function tryToTest(AcceptanceTester $I)
    //{
    //}
}
