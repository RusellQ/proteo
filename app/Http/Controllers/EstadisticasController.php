<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class EstadisticasController extends Controller
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

    private const ESTADO_LABELS = [
        'aprobados' => 'Aprobados',
        'pendientes' => 'Pendientes',
    ];

    /**
     * Lista plantillas (acervos) disponibles para filtrar estadísticas.
     */
    public function plantillas()
    {
        $titles = $this->titlesFromModels();
        $plantillas = [];

        foreach ($this->listBasePlantillas() as $nombre) {
            $plantillas[] = [
                'id' => $nombre,
                'nombre' => $titles[$nombre] ?? $nombre,
                'codigo' => $nombre,
            ];
        }

        usort($plantillas, fn ($a, $b) => strcasecmp($a['nombre'], $b['nombre']));

        return response()->json($plantillas);
    }

    /**
     * Conteos de documentos aprobados / pendientes, filtrados por plantilla.
     *
     * Varios estados → la gráfica se parte por estado.
     * Un solo estado → la gráfica se parte por plantilla.
     */
    public function estadisticas(Request $request)
    {
        $validated = $request->validate([
            'estados' => 'required|array|min:1',
            'estados.*' => 'in:aprobados,pendientes',
            'plantillas' => 'sometimes|array',
            'plantillas.*' => 'string',
        ]);

        $estados = array_values(array_unique($validated['estados']));
        $todas = $this->listBasePlantillas();
        $seleccionadas = $validated['plantillas'] ?? [];

        if (empty($seleccionadas)) {
            $plantillas = $todas;
        } else {
            $plantillas = array_values(array_intersect($todas, $seleccionadas));
        }

        $titles = $this->titlesFromModels();
        $porPlantilla = [];
        $kpi = ['aprobados' => 0, 'pendientes' => 0];

        foreach ($plantillas as $nombre) {
            $aprobados = $this->countCollection($nombre);
            $pendientes = $this->countCollection($nombre . '_pendientes');
            $label = $titles[$nombre] ?? $nombre;

            $porPlantilla[] = [
                'id' => $nombre,
                'nombre' => $label,
                'aprobados' => $aprobados,
                'pendientes' => $pendientes,
            ];

            $kpi['aprobados'] += $aprobados;
            $kpi['pendientes'] += $pendientes;
        }

        $labels = [];
        $data = [];
        $dimensionLabel = '';
        $filtroResumen = '';

        if (count($estados) > 1) {
            foreach ($estados as $estado) {
                $labels[] = self::ESTADO_LABELS[$estado];
                $data[] = $kpi[$estado] ?? 0;
            }
            $dimensionLabel = 'Por estado';
            $filtroResumen = $this->resumenPlantillas($plantillas, $titles)
                . ' · ' . implode(' + ', array_map(fn ($e) => self::ESTADO_LABELS[$e], $estados));
        } else {
            $estado = $estados[0];
            foreach ($porPlantilla as $row) {
                $labels[] = $row['nombre'];
                $data[] = $row[$estado] ?? 0;
            }
            $dimensionLabel = 'Por plantilla · ' . self::ESTADO_LABELS[$estado];
            $filtroResumen = self::ESTADO_LABELS[$estado]
                . ' · ' . $this->resumenPlantillas($plantillas, $titles);
        }

        $total = array_sum($data);

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'total' => $total,
            'dimension_label' => $dimensionLabel,
            'filtro_resumen' => $filtroResumen,
            'kpi' => $kpi,
            'alcance' => 'global',
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

    private function countCollection(string $name): int
    {
        try {
            return (int) DB::connection('mongodb')->collection($name)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function titlesFromModels(): array
    {
        $titles = [];
        $modelosPath = app_path('Models');

        if (!is_dir($modelosPath)) {
            return $titles;
        }

        foreach (File::allFiles($modelosPath) as $archivo) {
            $nombreClase = 'App\\Models\\' . basename($archivo, '.php');
            if (!class_exists($nombreClase)) {
                continue;
            }

            $reflexion = new ReflectionClass($nombreClase);
            if (!$reflexion->isSubclassOf('MongoDB\\Laravel\\Eloquent\\Model')) {
                continue;
            }

            $base = class_basename($nombreClase);
            $title = $reflexion->hasProperty('title') ? ($nombreClase::$title ?? $base) : $base;
            $titles[$base] = $title;
        }

        return $titles;
    }

    private function resumenPlantillas(array $plantillas, array $titles): string
    {
        $count = count($plantillas);
        if ($count === 0) {
            return 'Sin plantillas';
        }
        if ($count === 1) {
            $id = $plantillas[0];
            return $titles[$id] ?? $id;
        }

        return $count . ' plantillas';
    }
}
