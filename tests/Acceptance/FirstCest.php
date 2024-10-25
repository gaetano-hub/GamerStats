<?php


namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class FirstCest
{
    public function signUp(AcceptanceTester $I)
    {
        $I->amOnPage('/home/home.php');
        $I->click('Sign Up');
        $I->seeInCurrentUrl('/signUp/signUp.html');
        $I->fillField('Nickname', 'gaetest');
        $I->fillField('Email address', 'gaetest@example.com'); 
        $I->fillField('Password', 'password');
        $I->fillField('Confirm Password', 'password'); 
        $I->click('Confirm Registration');
        $I->seeInCurrentUrl('/login/login.html');
    }

    // tests
    //public function tryToTest(AcceptanceTester $I)
    //{
    //}
}
