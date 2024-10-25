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
        $I->see('gaetest');
    }

    // tests
    //public function tryToTest(AcceptanceTester $I)
    //{
    //}
}
