<?php

namespace IrbisApps\Stock;

use Irbis\Controller as iController;


class Controller extends iController {
    public $name = 'stock';
    public $installable = True;
    public $depends = [
        'IrbisApps/Product',
    ];
}