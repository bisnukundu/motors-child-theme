<?php
if (!defined('ABSPATH')) {
    die("Go Back.");
}


define("BK_FILE_PATH", get_stylesheet_directory());

require_once(BK_FILE_PATH . "/Backend/Controller/Car_Filter_By_Tag.php");

use Bisnu\Backend\Controller\Car_Filter_By_Tag;


new Car_Filter_By_Tag();
