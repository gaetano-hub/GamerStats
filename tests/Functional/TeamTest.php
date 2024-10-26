<?php

// Includi le classi Leader e Team
require_once './app/Leader.php';
require_once './app/Team.php';

class TeamCreationPostTest extends \Codeception\Test\Unit
{
    /**
     * @var \FunctionalTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // Test per la creazione del team tramite POST con leader
    public function testTeamCreationViaPostWithLeader()
    {
        // Simulazione con classi
        $leader = new Leader('gaetest', 'gaetest@example.com', 'password', '', '');

        $this->assertInstanceOf(Leader::class, $leader);

        $testLeaderData = [
            'nickname' => 'gaetest',
            'email' => 'gaetest@example.com',
            'password' => 'password',
            'steamID' => '',
            'image' => '',
        ];

        $this->assertEquals($testLeaderData, $leader->getData());

        $team = new Team('TeamTest','Csgo','gaetano','','','','',$leader->getData()['nickname']);

        $this->assertInstanceOf(Team::class, $team);

        $testData = [
            'teamName' => 'TeamTest',
            'game' => 'Csgo',
            'memberOne' => 'gaetano',
            'memberTwo' => '',
            'memberThree' => '',
            'memberFour' => '',
            'memberFive' => '',
            'nickname' => 'gaetest',
        ];

        $teamData = $team->getData();

        $this->assertEquals($testData, $teamData);

        $newTeam = $leader->createTeam('TeamTest', 'Csgo', 'gaetano', '', '', '', '', $leader->getData()['nickname']);

        $teamData2 = $newTeam->getData();

        $this->assertEquals($testData, $teamData2);

        // Effettivo test sul database
        $postData = [
            'teamName' => 'TeamTest2',
            'game' => 'Csgo',
            'memberOne' => 'gaetano',
            'memberTwo' => '',
            'memberThree' => '',
            'memberFour' => '',
            'memberFive' => '',
            'nickname' => 'gaetest',
        ];

        $this->tester->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->tester->sendPOST('/app/team_creation.php', $postData);

        $newTeam = $leader->createTeam('TeamTest2', 'Csgo', 'gaetano', '', '', '', '', $leader->getData()['nickname']);

        $this->tester->seeResponseContains("Team created successfully");

        $this->tester->seeInDatabase('teams', [
            'team_name' => 'TeamTest2',
            'game' => 'Csgo',
            'member_one' => 'gaetano',
            'member_two' => '',
            'member_three' => '',
            'member_four' => '',
            'member_five' => '',
            'leader' => 'gaetest',
            
        ]);
    }
}

