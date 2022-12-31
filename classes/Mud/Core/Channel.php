<?php

namespace Menking\Mud\Core;

class Channel {
    private $state, $data;
    private $questions, $answers = [];
    private $playerId = null, $socketId;

    public function __construct($socketId) {
        $this->socketId = $socketId;
        $this->questions = json_decode(file_get_contents('data/questions.json'), true);
    }

    public function begin($state = 'login-prompt', $keep_data = false) {
        if( !$keep_data ) $this->answers = [];
        $this->state = $state;

        return $this->questions[$this->state]['message'];
    }

    public function getPlayerId() {
        return $this->playerId;
    }

    public function setPlayerId($player_id) {
        $this->playerId = $player_id;
    }

    public function getSocketId() {
        return $this->socketId;
    }

    public function processAnswer($answer) {
        $current_question = $this->questions[$this->state];

        switch($current_question['answer-type']) {
            case "string":
                if( is_string($answer) ) {
                    if( isset($current_question['terminator']) && $current_question['terminator'] ) {
                        $this->answers[$this->state] = $answer;
                        return ["completed"=>true, "data"=>$this->answers, "state"=>$this->state];
                    }
                    else {
                        $this->answers[$this->state] = $answer;
                        $this->state = $current_question['correct-next-step'];
                        return ["completed"=>false, "data"=>$this->questions[$this->state]['message'],
                            "state"=>$this->state];
                    }
                }
                else {
                     $this->state = $current_question['incorrect-next-step'];
                    return ["completed"=>false, "data"=>$this->questions[$this->state]['message'],
                        "state"=>$this->state];
                }
            break;
            case "choice":
                if( in_array($answer, $current_question['choices']) ) {
                    if( isset($current_question['terminator']) && $current_question['terminator'] ) {
                        $this->answers[$this->state] = $answer;
                        return ["completed"=>true, "data"=>$this->answers, "state"=>$this->state];
                    }
                    else {
                        $this->answers[$this->state] = $answer;
                        $this->state = $current_question['correct-next-step'];
                        return ["completed"=>false, "data"=>$this->questions[$this->state]['message'],
                            "state"=>$this->state];
                    }
                }
                else {
                    $this->state = $current_question['incorrect-next-step'];
                    return ["completed"=>false, "data"=>$this->questions[$this->state]['message'],
                        "state"=>$this->state];
                }
            break;
            case "email":
                if( $email = filter_var($answer, FILTER_VALIDATE_EMAIL) ) {

                    if( isset($current_question['terminator']) && $current_question['terminator'] ) {
                        $this->answers[$this->state] = $answer;
                        return ["completed"=>true, "data"=>$this->answers, "state"=>$this->state];
                    }
                    else {
                        $this->answers[$this->state] = $answer;
                        $this->state = $current_question['correct-next-step'];
                        return ["completed"=>false, "data"=>$this->questions[$this->state]['message'], 
                            "state"=>$this->state];
                    }                    
                }
                else {
                    $this->state = $current_question['incorrect-next-step'];
                    return ["completed"=>false, "data"=>$this->questions[$this->state]['message'], 
                        "state"=>$this->state];
                }
            break;
        }
    }
}