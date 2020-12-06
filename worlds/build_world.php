<?php

if( $argc <> 3 ) {
    die("usage: {$argv[0]} <world name> <draw.io XML file>\n\n");
}

$world_name = $argv[1];
$world_file = $argv[2];

if( !file_exists($world_file) ) die("Sorry, cannot find or open file '$world_file'\n\n");

echo "Processing file...\n";

$xml = simplexml_load_file($world_file);

if( $xml['host'] != 'app.diagrams.net') die("Sorry, this is not a properly formatted file\n\n");

$world = ['world-name'=>$world_name, 'rooms'=>[]];

$id_table = [];

foreach($xml->diagram->mxGraphModel->root->children() as $tag=>$obj) {
    if( $tag === 'object' ) {
        $id = (string)$obj['id'];
        $world['rooms'][$id] = [
            'id'=>$id,
            'room-name'=>(string)$obj['name'],
            'temperature'=>(string)$obj['temperature'],
            'ambiance'=>(string)$obj['ambiance'],
            'description'=>(string)$obj['description'],
            'oxygen_level'=>(string)$obj['oxygen_level'],
            'light_level'=>(string)$obj['light_level'],
            'x'=>$obj->mxCell->mxGeometry['x'],
            'y'=>$obj->mxCell->mxGeometry['y'],
            'width'=>$obj->mxCell->mxGeometry['width'],
            'height'=>$obj->mxCell->mxGeometry['height'],
            'exits'=>[]
        ];

        $styles = explode(';', (string)$obj->mxCell['style']);
        foreach($styles as $style) {
            $v = explode('=', $style);

            if( $v[0] == 'fillColor' && $v[1] == '#d5e8d4' ) {
                $world['rooms'][$id]['spawn'] = true;
            }
        }

    }
    else if( $tag === 'mxCell' ) {
        if( isset($obj['source']) && isset($obj['target']) ) {
            $kvs = breakdown($obj['style']);
            $locks = ['source'=>false, 'target'=>false];
            if( isset($kvs['startArrow']) && $kvs['startArrow'] == 'oval' ) {
                $locks['source'] = true;
            }
            else if( isset($kvs['endArrow']) && $kvs['endArrow'] == 'oval' ) {
                $locks['target'] = true;
            }

            $id_table[] = ['source'=>(string)$obj['source'], 'target'=>(string)$obj['target'], 'locks'=>$locks];
        }
    }
}

$opposites = ['north'=>'south', 'east'=>'west', 'south'=>'north', 'west'=>'east'];

foreach($id_table as $map) {
    $exit = determine_position($world['rooms'][$map['source']], $world['rooms'][$map['target']]);

    echo "map['target'] = {$map['target']}\n";
    echo "map['source'] = {$map['source']}\n";
    echo "locks['target'] = {$map['locks']['target']}\n";
    echo "locks['source'] = {$map['locks']['source']}\n";

    $world['rooms'][$map['source']]['exits'][$opposites[$exit]] = 
        ['target'=>$map['target'], 'locked'=>$map['locks']['target']];

    $world['rooms'][$map['target']]['exits'][$exit] = 
        ['target'=>$map['source'], 'locked'=>$map['locks']['source']];
}

foreach($world['rooms'] as $id=>$data) {
    unset($world['rooms'][$id]['x']);
    unset($world['rooms'][$id]['y']);
    unset($world['rooms'][$id]['height']);
    unset($world['rooms'][$id]['width']);

}

file_put_contents($world_name . '.json', json_encode($world, JSON_PRETTY_PRINT));

echo "Conversion complete\n";
exit;

function determine_position($objectA, $objectB) {
    $objectA_midx = $objectA['x'] + ($objectA['width'] / 2);
    $objectB_midx = $objectB['x'] + ($objectB['width'] / 2);
    $variance_x = $objectA['width'] / 2;

    $objectA_midy = $objectA['y'] + ($objectA['height'] / 2);
    $objectB_midy = $objectB['y'] + ($objectB['height'] / 2);
    $variance_y = $objectA['height'] / 2;

    if( $objectB_midx >= ($objectA_midx - $variance_x ) && $objectB_midx <= ($objectA_midx + $variance_x) ) {
        if( $objectA_midy < $objectB_midy ) {
             return 'north';
        }
        else {
            return 'south';
        }
    }
    else if( $objectB_midy >= ($objectA_midy - $variance_y) && $objectB_midy <= ($objectA_midy + $variance_y) ) { // either east or west
        if( $objectA_midx < $objectB_midx ) {
             return 'west';
        }
        else {
            return 'east';
        }
    }
    else {
        echo "Something went wrong\n";
        return false;
    }
}

function breakdown($data) {
    $r = [];

    foreach(explode(';', $data) as $pair) {
        $x = explode('=', $pair);
        if( !empty($x[0]) ) {
            $r[$x[0]] = $x[1];
        }
    }

    return $r;
}