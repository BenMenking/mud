<?php

class Item {
    private $volume, $weight;
    private $type, $group, $name;

    private function __construct($name) {
        $file = "items/" . $this->pathify($name) . ".json";

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
}

class ItemGroup {
    const GROUP_WEAPON = "weapon";
    const GROUP_CONSUMABLE = "consumable";
    const GROUP_ARMOR = "armor";
    const GROUP_SHIELD = "shield";

}