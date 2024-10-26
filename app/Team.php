<?php

class Team
{
    public $team_name;
    public $game;
    public $member_one;
    public $member_two;
    public $member_three;
    public $member_four;
    public $member_five;
    public $leader;

    public function __construct($team_name, $game, $member_one, $member_two, $member_three, $member_four, $member_five, $leader)
    {
        $this->team_name = $team_name;
        $this->game = $game;
        $this->member_one = $member_one;
        $this->member_two = $member_two;
        $this->member_three = $member_three;
        $this->member_four = $member_four;
        $this->member_five = $member_five;
        $this->leader = $leader;
    }

    // Metodo per ottenere i dati del team
    public function getData()
    {
        return [
            'teamName' => $this->team_name,
            'game' => $this->game,
            'memberOne' => $this->member_one,
            'memberTwo' => $this->member_two,
            'memberThree' => $this->member_three,
            'memberFour' => $this->member_four,
            'memberFive' => $this->member_five,
            'nickname' => $this->leader
        ];
    }
}
