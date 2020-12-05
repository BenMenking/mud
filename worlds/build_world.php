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

$diagram = $xml->diagram;