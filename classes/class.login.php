<?php

class Login {
    private $uuid, $state, $data;
    private $questions, $answers = [];

    public function __construct($uuid) {
        $this->uuid = $uuid;

        $this->questions = json_decode(file_get_contents('questions.json'), true);
    }

    public function begin($state = 'login-prompt', $keep_data = false) {
        if( !$keep_data ) $this->answers = [];
        $this->state = $state;

        return $this->questions[$this->state]['message'];
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

    public function uuid() { return $this->uuid; }
}