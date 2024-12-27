<?php
namespace IrbisApps\Tools;

use Irbis\Controller as iController;


class Controller extends iController {
	public $name 			= 'tools';
	public $has_routes 		= false;
	public $installable 	= false;
	public $depends 		= ['IrbisApps/AdapterTwig'];
}