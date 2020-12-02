<?php

require_once('class.server.php');
require_once('class.login.php');
//require_once('class.client.php');
require_once('class.command.php');
require_once('class.world.php');
require_once('class.player.php');
require_once('class.questions.php');

class Terminal {
    const BLACK = "\033[30m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const LIGHT_BLACK = "\033[1;30m";
    const LIGHT_RED = "\033[1;31m";
    const LIGHT_GREEN = "\033[1;32m";
    const LIGHT_YELLOW = "\033[1;33m";
    const LIGHT_BLUE = "\033[1;34m";
    const LIGHT_MAGENTA = "\033[1;35m";
    const LIGHT_CYAN = "\033[1;36m";
    const LIGHT_WHITE = "\033[1;37m";    
    const RESET = "\033[0;0m";

    const BOLD = "\033[1m";
    const UNDERLINE = "\033[4m";
    const REVERSED = "\033[7m";

    /*
00 Black	000	000	000	000000	[0;30m  █████  █████
01 Blue		000	000	168	0000a8	[0;34m  █████  █████
02 Green	000	168	000	00a800	[0;32m  █████  █████
03 Cyan		000	168	168	00a8a8	[0;36m  █████  █████
04 Red		168	000	000	a80000	[0;31m  █████  █████
05 Magenta	168	000	168	a800a8	[0;35m  █████  █████
06 Yellow	168	084	000	a85400	[0;33m  █████  █████
07 White	168	168	168	a8a8a8	[0;37m  █████  █████
08 Light Black	084	084	084	545454	[1;30m  █████  █████
09 Light Blue	084	084	252	5454fc	[1;34m  █████  █████
10 Light Green	084	252	084	54fc54	[1;32m  █████  █████
11 Light Cyan	084	252	252	54fcfc	[1;36m  █████  █████
12 Light Red	252	084	084	fc5454	[1;31m  █████  █████
13 Light Magent	252	084	252	fc54fc	[1;35m  █████  █████
14 Light Yellow	252	252	084	fcfc54	[1;33m  █████  █████
15 Light White	252	252	252	fcfcfc	[1;37m  █████  █████
    */
}
?>