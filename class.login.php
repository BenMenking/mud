<?php

class Login {
    private $uuid, $state;
    private $questions;

    public function __construct($uuid) {
        $this->uuid = $uuid;

        $this->questions = json_decode(file_get_contents('questions.json'), true);
    }

    public function begin() {
        $this->state = "login-prompt";

        return $this->questions[$this->state]['message'];
    }

    public function processAnswer($answer) {
        $current_question = $this->questions[$this->state];

        switch($current_question['answer-type']) {
            case "string":
                if( is_string($answer) ) {
                    echo "LOGIN: proper string\n";
                    if( isset($current_question['terminator']) && $current_question['terminator'] ) {
                        return true;
                    }
                    else {
                        $this->state = $current_question['correct-next-step'];
                        return $this->questions[$this->state]['message'];
                    }
                }
                else {
                    echo "LOGIN: improper string\n";
                    $this->state = $current_question['incorrect-next-step'];
                    return $this->questions[$this->state]['message'];
                }
            break;
            case "choice":
                if( in_array($answer, $current_question['choices']) ) {
                    if( isset($current_question['terminator']) && $current_question['terminator'] ) {
                        return true;
                    }
                    else {
                        $this->state = $current_question['correct-next-step'];
                        return $this->questions[$this->state]['message'];
                    }
                }
                else {
                    $this->state = $current_question['incorrect-next-step'];
                    return $this->questions[$this->state]['message'];
                }
            break;
            case "email":
                if( $email = filter_var($answer, FILTER_VALIDATE_EMAIL) ) {

                    if( isset($current_question['terminator']) && $current_question['terminator'] ) {
                        return true;
                    }
                    else {
                        $this->state = $current_question['incorrect-next-step'];
                        return $this->questions[$this->state]['message'];
                    }                    
                }
                else {
                    $this->state = $current_question['incorrect-next-step'];
                    return $this->questions[$this->state]['message'];
                }
            break;
        }
    }

    public function uuid() { return $this->uuid; }
}