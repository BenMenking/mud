<?php

class User {
    // volatile variables that do not get permanently recorded
    public $state, $authenticated = false, $messages = [], $room;

    // non-volatile variables that get saved to user record
    protected $meta, $userfile;

    public function performAction(Command $command) {
        return $this->state->perform($command);
    }

    public static function load($name) {
        $file = "users/" . User::pathify($name) . ".json";

        if( file_exists($file) ) {
            $instance = new self();

            $instance->meta = json_decode(file_get_contents($file), true);
            $instance->userfile = $file;
            $instance->state = new StandingState();

            return $instance;
        }
        else {
            throw new Exception('User does not exist');
        }
    }

    public function setRoom(Room $room) {
        $this->room = $room;
    }

    public function save() {
        return file_put_contents($this->userfile, json_encode($this->meta, JSON_PRETTY_PRINT));
    }

    public function authenticate($password) {
        if( password_verify($password, $this->meta['password']) ) {
            $this->authenticated = true;
            return true;
        }
        else {
            return false;
        }
    }

    public static function create($name, $class, $race, $attributes, $password, $email_address, $starting_room) {
        $instance = new self();

        $file = "users/" . User::pathify($name) . ".json";
        $instance->meta['name'] = $name;
        $instance->meta['class'] = 'cleric';
        $instance->meta['race'] = 'human';
        $instance->meta['attributes'] = $attributes;
        $instance->meta['skill_points'] = 0;
        $instance->meta['health'] = 100; // 0-100 (but modifiers could take above 100)
        $instance->meta['total_health'] = 100;
        $instance->meta['mana'] = 0;
        $instance->meta['total_mana'] = 0;
        $instance->meta['movement'] = 25;
        $instance->meta['total_movement'] = 25;
        $instance->meta['hunger'] = 0; // 0-5 with 0 no hunger, 3 hungry, 5 starving
        $instance->meta['thirst'] = 0; // 0-5 with 0 no thirst, 3 parched, 5 dehydrated
        $instance->meta['temperature'] = 96.7; // in farenheit
        $instance->meta['alignment'] = 0;
        $instance->meta['wearables'] = [];
        $instance->meta['room'] = $starting_room;
        $instance->meta['wallet'] = 100; // starting coins
        $instance->meta['carrying'] = [];
        $instance->meta['weight'] = 175; // in pounds
        $instance->meta['o2'] = 99; // blood's oxygen level
        $instance->meta['password'] = password_hash($password, PASSWORD_DEFAULT);
        $instance->meta['email'] = $email_address;
        $instance->meta['created'] = time();
        $instance->meta['last_login'] = time();

        $instance->state = new StandingState();
        $instance->userfile = $file;

        $instance->save();

        return $instance;
    }

    public function name() { return $this->meta['name']; }
    public function class() { return $this->meta['class']; }
    public function race() { return $this->meta['race']; }

    public function base() { return $this->meta['attributes']; }
    public function room() { return $this->meta['room']; }

    public function prompt() {
        return "<H:{$this->meta['health']}/{$this->meta['total_health']} "
            . "M:{$this->meta['mana']}/{$this->meta['total_mana']} "
            . "MV:{$this->meta['movement']}/{$this->meta['total_movement']} "
            . "G:{$this->meta['wallet']}> ";
    }

    private static function pathify($file) {
        /** https://stackoverflow.com/a/2021729/4508285 */
        // Remove anything which isn't a word, whitespace, number
        // or any of the following caracters -_~,;[]().
        // If you don't need to handle multi-byte characters
        // you can use preg_replace rather than mb_ereg_replace
        // Thanks @Łukasz Rysiak!
        $file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', strtolower($file));
        // Remove any runs of periods (thanks falstro!)
        return mb_ereg_replace("([\.]{2,})", '', $file);        
    }


}

class UserStates {
    public function perform(Command $command) { }
}

class StandingState extends UserStates {
    public function perform(Command $command) {
        parent::perform($command);

        switch(true) {
            case $command instanceof LookCommand:
                return $command->perform();
            break;
        }
    }
}

class RestingState extends UserStates {
    public function perform(Command $command) {
        parent::perform($command);

        switch(true) {
            case $command instanceof LookCommand:
                return $command->perform();
            break;
        }
    }
}

class SleepingState extends UserStates {
    public function perform(Command $command) {
        parent::perform($command);

        switch(true) {
            case $command instanceof LookCommand:
                return "You cannot look while sleeping!";
            break;
        }
    }
}

class IncapacitatedState extends UserStates {
    public function perform(Command $command) {
        parent::perform($command);

        switch(true) {
            case $command instanceof LookCommand:
                $command->perform();
            break;
        }
    }
}
