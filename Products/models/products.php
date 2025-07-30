<?php

return [
    '__label' => 'Productos',
    '__form_view' => '@product/views/product_form.html',
    '__list_view' => '@product/views/product_list.html',

    'name' => ['varchar', 'required'=>true, 'label'=>'Nombre'],
    'code' => ['varchar', 'label'=>'Código'],
    'category' => ['n1', 'target'=>'product_categories', 'label'=>'Categoría']
];