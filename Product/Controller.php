<?php

namespace IrbisApps\Product;

use Irbis\Controller as iController;
use Irbis\RecordSet;


class Controller extends iController {
    public $name = 'product';
    public $has_routes = false;
    public $installable = true;
    public $depends = [];

    /**
     * El administrador expone algunos modelos por interfaz
     * para que el usuario los pueda gestionar, ver y editar
     * el esquema es:
     * [
     *    'Nombre del módulo' => [ # o categoría
     *       'modelo' => ['label' => 'nombre del modelo']
     *     ]
     * ]
     * 
     * El nombre del módulo es la agrupación de modelos para
     * que la lista no sea tan extensa y esté organizada
     */
    public $models = [
        'Productos' => [
            'products' => ['label' => 'Productos'],
            'product_categories' => ['label' => 'Categorías de productos']
        ]
    ];

    public function assemble () {
        $products = new RecordSet('products');
        $products->bind();

        $categories = new RecordSet('product_categories');
        $categories->bind();
        $categories->insert([
            'name' => 'Productos'
        ]);
    }
}