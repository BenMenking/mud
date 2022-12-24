<?php

namespace Menking\Mud\Core;

class Questions {
    private $questions;

    public function __construct() {
        $this->questions = json_decode(file_get_contents('data/questions.json'), true);
    }

    public function byId($id) {
        return (isset($this->questions[$id])?$this->questions[$id]:false);
    }

    public function validate($id, $answer) {
        $question = $this->questions[$id];

        switch($question['answer_type']) {
            case 'regex':
                if( preg_match($question['answer'], $answer, $matches) ) {
                    return $matches;
                }
                else {
                    return false;
                }
            break;

            case 'string': 
                return $answer === $question['answer'];
            break;

            case 'int': 
                if( $answer = filter_var($answer, FILTER_VALIDATE_INT)) {
                    return $answer === (int)$question['answer'];
                }
                else {
                    return false;
                }
            break;

            case 'choice':
                return in_array($answer, $question['answer']);
            break;

            default:
                return false;
        }
    }
}
