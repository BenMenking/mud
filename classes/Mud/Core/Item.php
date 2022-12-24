<?php

namespace Menking\Mud\Core;

class Item {
    private $volume, $weight;
    private $type, $group, $name;

    private function __construct($name) {
        $file = "items/" . Item::pathify($name) . ".json";

        echo "Attempting to load item in '$file'\n";
        $data = json_decode(file_get_contents($file), true);

        $this->__load($data);
    }

    public static function load($name) {

    }

    private function __load($data) {
        $this->volume = $data['volume'];
        $this->weight = $data['weight'];
        $this->type = $data['type'];
        $this->group = $data['group'];
        $this->name = $data['name'];
    }

    private function __save() {

    }

    public function name() { return $this->name; }
    public function volume() { return $this->volume; }
    public function weight() { return $this->weight; }
    public function type() { return $this->type; }
    public function group() { return $this->group; }

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
