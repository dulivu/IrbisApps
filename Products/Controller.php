<?php

namespace IrbisApps\Products;

use Irbis\Controller as iController;
use Irbis\RecordSet;


class Controller extends iController {
    public static $name = 'product';
    public static $use_routes = true;
    public static $depends = ['IrbisApps/Base'];

    // public function assemble () {
    //     $products = new RecordSet('products');
    //     $products->bind();

    //     $categories = new RecordSet('product_categories');
    //     $categories->bind();
    //     $categories->insert([
    //         'name' => 'Productos'
    //     ]);
    // }
}