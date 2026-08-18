<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class PlanosActivos extends Model
{   
    protected $connection = 'mongodb';

    protected $collection = 'PlanosActivos_pendientes'; // Establece el nombre de la colección

    protected $guarded = ['_id', 'Municipio_o_Estado', 'Nomenclatura', 'Año', 'Título', 'Autor', 'Fuente', 'Expediente', 'Escala', 'Medidas', 'Recurso digital'];

    // Define los tipos de datos de los campos de manera estática
    public static $fieldTypes = [
        'Municipio_o_Estado' => 'string', 'Nomenclatura' => 'string', 'Año' => 'string', 'Título' => 'string', 'Autor' => 'string', 'Fuente' => 'string', 'Expediente' => 'string', 'Escala' => 'string', 'Medidas' => 'string','Recurso digital' => 'file' ,
    ];

    // Define alias para los campos
    public static $fieldAliases = [
        'Municipio_o_Estado' => 'Municipio ó Estado', 'Nomenclatura' => 'Nomenclatura', 'Año' => 'Año', 'Título' => 'Título', 'Autor' => 'Autor', 'Fuente' => 'Fuente', 'Expediente' => 'Expediente', 'Escala' => 'Escala', 'Medidas' => 'Medidas', 'Recurso digital' => 'Recurso Digital', 
    ];

    // Define los campos obligatorios
    public static $requiredFields = [
        
    ];

    // Define el nombre con espacios
    public static $title = 'Planos Activos'; // Nombre original de la plantilla con espacios

}