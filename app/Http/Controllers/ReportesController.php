<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use ReflectionClass;

class ReportesController extends Controller
{
    private const EXCLUDED_COLLECTIONS = [
        'failed_jobs',
        'navbar_colors',
        'password_reset_tokens',
        'migrations',
        'personal_access_tokens',
        'plantillas_predeterminadas',
        'noticias_collection',
        'users',
        'comentarios',
        'carrousel_images',
        'CarrouselImage',
        'Comentario',
        'NavbarColor',
        'Noticia',
        'User',
        'PlantillaPredeterminada',
        'visitas',
        'cache',
        'jobs',
        'sessions',
        'password_resets',
    ];

    public function show(string $plantillaName)
    {
        if (!preg_match('/^[\p{L}\p{N}_-]+$/u', $plantillaName)) {
            return response()->json(['message' => 'Colección no válida.'], 404);
        }

        $permitidas = $this->listBasePlantillas();
        if (!in_array($plantillaName, $permitidas, true)) {
            return response()->json(['message' => 'Colección no encontrada.'], 404);
        }

        $modelo = $this->modeloDe($plantillaName);
        $fieldTypes = $modelo['types'];
        $fieldAliases = $modelo['aliases'];

        $aprobados = $this->fetchDocuments($plantillaName);
        $pendientes = $this->fetchDocuments($plantillaName . '_pendientes');

        $columnas = $this->columnas($fieldTypes, $fieldAliases, array_merge($aprobados, $pendientes));

        $documentos = [];
        $conRecurso = 0;

        foreach ($aprobados as $doc) {
            $fila = $this->fila($doc, 'aprobado', $columnas, $fieldTypes);
            if ($fila['tiene_recurso']) {
                $conRecurso++;
            }
            $documentos[] = $fila;
        }

        foreach ($pendientes as $doc) {
            $fila = $this->fila($doc, 'pendiente', $columnas, $fieldTypes);
            if ($fila['tiene_recurso']) {
                $conRecurso++;
            }
            $documentos[] = $fila;
        }

        return response()->json([
            'plantilla' => $plantillaName,
            'nombre' => $modelo['title'],
            'generado_en' => Carbon::now('America/Cancun')->format('d/m/Y H:i'),
            'resumen' => [
                'aprobados' => count($aprobados),
                'pendientes' => count($pendientes),
                'total' => count($aprobados) + count($pendientes),
                'con_recurso' => $conRecurso,
            ],
            'columnas' => $columnas,
            'documentos' => $documentos,
        ]);
    }

    private function listBasePlantillas(): array
    {
        $collections = DB::connection('mongodb')->listCollections();
        $names = [];

        foreach ($collections as $collection) {
            $name = $collection->getName();

            if (in_array($name, self::EXCLUDED_COLLECTIONS, true)) {
                continue;
            }
            if (str_ends_with($name, '_pendientes') || str_ends_with($name, '_originales')) {
                continue;
            }

            $names[] = $name;
        }

        sort($names);

        return $names;
    }

    private function modeloDe(string $plantillaName): array
    {
        $class = 'App\\Models\\' . $plantillaName;
        $title = $plantillaName;
        $types = [];
        $aliases = [];

        if (!class_exists($class)) {
            return compact('title', 'types', 'aliases');
        }

        $reflexion = new ReflectionClass($class);
        if (!$reflexion->isSubclassOf('MongoDB\\Laravel\\Eloquent\\Model')) {
            return compact('title', 'types', 'aliases');
        }

        if ($reflexion->hasProperty('title')) {
            $title = $class::$title ?? $plantillaName;
        }
        if ($reflexion->hasProperty('fieldTypes')) {
            $types = $class::$fieldTypes ?? [];
        }
        if ($reflexion->hasProperty('fieldAliases')) {
            $aliases = $class::$fieldAliases ?? [];
        }

        return compact('title', 'types', 'aliases');
    }

    private function fetchDocuments(string $name): array
    {
        try {
            $rows = DB::connection('mongodb')->collection($name)->get();
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->toPlain($row);
        }

        return $out;
    }

    private function toPlain($value)
    {
        if ($value instanceof BSONDocument || $value instanceof BSONArray) {
            return $this->toPlain($value->getArrayCopy());
        }
        if ($value instanceof ObjectId) {
            return (string) $value;
        }
        if ($value instanceof UTCDateTime) {
            return $value->toDateTime()
                ->setTimezone(new \DateTimeZone('America/Cancun'))
                ->format('d/m/Y H:i');
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->timezone('America/Cancun')->format('d/m/Y H:i');
        }
        if (is_object($value)) {
            return $this->toPlain((array) $value);
        }
        if (is_array($value)) {
            $plain = [];
            foreach ($value as $key => $item) {
                $plain[$key] = $this->toPlain($item);
            }
            return $plain;
        }

        return $value;
    }

    private function columnas(array $fieldTypes, array $fieldAliases, array $documents): array
    {
        $keys = array_keys($fieldTypes);

        foreach ($documents as $doc) {
            foreach (array_keys($doc) as $key) {
                if ($key === '_id' || in_array($key, $keys, true)) {
                    continue;
                }
                $keys[] = $key;
            }
        }

        $tail = ['created_at', 'updated_at'];
        $body = array_values(array_filter($keys, fn ($key) => !in_array($key, $tail, true)));
        foreach ($tail as $key) {
            if (in_array($key, $keys, true)) {
                $body[] = $key;
            }
        }

        $labels = [
            'created_at' => 'Fecha de creación',
            'updated_at' => 'Última actualización',
        ];

        $columnas = [];
        foreach ($body as $key) {
            $columnas[] = [
                'key' => $key,
                'label' => $fieldAliases[$key] ?? $labels[$key] ?? $key,
            ];
        }

        return $columnas;
    }

    private function fila(array $doc, string $estado, array $columnas, array $fieldTypes): array
    {
        $id = $doc['_id'] ?? '';
        if (is_array($id)) {
            $id = $id['$oid'] ?? json_encode($id);
        }

        $valores = [];
        foreach ($columnas as $columna) {
            $key = $columna['key'];
            $raw = $doc[$key] ?? null;
            if (in_array($key, ['created_at', 'updated_at'], true) && is_string($raw) && $raw !== '') {
                $valores[$key] = $this->formatDate($raw);
            } else {
                $valores[$key] = $this->display($raw, $fieldTypes[$key] ?? null);
            }
        }

        $recurso = $valores['Recurso Digital'] ?? '';

        return [
            'id' => (string) $id,
            'estado' => $estado,
            'tiene_recurso' => $recurso !== '',
            'valores' => $valores,
        ];
    }

    private function formatDate(string $value): string
    {
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $value)) {
            return $value;
        }

        try {
            return Carbon::parse($value)->timezone('America/Cancun')->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private function display($value, ?string $type): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }
        if ($type === 'file' || $this->isFileList($value)) {
            return $this->formatFiles($value);
        }
        if (is_array($value)) {
            if (array_is_list($value)) {
                $parts = array_filter(array_map(fn ($item) => $this->display($item, null), $value), fn ($part) => $part !== '');
                return implode(', ', $parts);
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return trim((string) $value);
    }

    private function isFileList($value): bool
    {
        $items = null;
        if (is_string($value)) {
            $items = [$value];
        } elseif (is_array($value) && array_is_list($value)) {
            $items = $value;
        }

        if ($items === null || $items === []) {
            return false;
        }

        foreach ($items as $item) {
            if (!is_string($item) || !preg_match('/\.(pdf|jpe?g|png|gif|tiff?|mp4|mp3|wav)$/i', $item)) {
                return false;
            }
        }

        return true;
    }

    private function formatFiles($value): string
    {
        $files = is_array($value) ? $value : [$value];
        $names = [];
        foreach ($files as $file) {
            if (!is_string($file) || $file === '') {
                continue;
            }
            $names[] = basename($file);
        }

        if ($names === []) {
            return '';
        }

        $n = count($names);
        $label = $n === 1 ? '1 archivo' : $n . ' archivos';

        return $label . ': ' . implode(', ', $names);
    }
}
