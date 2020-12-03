<?php

require_once('class.server.php');
require_once('class.login.php');
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
}
?>