<?php

return [
    "name" => ["varchar", "required" => true, "label" => "Nombre"],
    "sequence" => ["tinyint", "default" => 1, "label" => "Secuencia"],
    "project" => ["n1", "target" => "project", "label" => "Proyecto"],
    "tasks" => ["1n", "target" => "project_task(stage)", "label" => "Tareas"],

    "is_open" => ["boolean", "default" => true, "label" => "Abierto"],
];