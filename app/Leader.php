<?php

class Leader
{
    public $nickname;
    public $email;
    public $password;
    public $steamID;
    public $image;
    

    public function __construct($nickname, $email, $password, $steamID, $image)
    {
        $this->nickname = $nickname;
        $this->email = $email;
        $this->password = $password;
        $this->steamID = $steamID;
        $this->image = $image;
    }

    // Metodo per ottenere i dati del leader
    public function getData()
    {
        return [
            'nickname' => $this->nickname,
            'email' => $this->email,
            'password' => $this->password,
            'steamID' => $this->steamID,
            'image' => $this->image

        ];
    }
    public function createTeam($team_name, $game, $member_one, $member_two, $member_three, $member_four, $member_five, $leader)
    {
        return new Team($team_name, $game, $member_one, $member_two, $member_three, $member_four, $member_five, $leader);
    }
}
