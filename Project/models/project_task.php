<?php

return [
    "name" => ["varchar", "required" => true, "label" => "Nombre"],
    "description" => ["text", "label" => "Descripción"],
    "time_spent" => ["decimal(2,2)", "label" => "Tiempo invertido"],
    "stage" => ["n1", "target" => "project_stage", "label" => "Etapa"],
];