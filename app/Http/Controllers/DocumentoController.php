<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MongoDB\Client;  // Asegúrate de importar el cliente de MongoDB
use Illuminate\Support\Facades\Validator;
use ReflectionClass; // Importa ReflectionClass
use Illuminate\Support\Str; // Asegúrate de importar la clase Str
use setasign\Fpdi\TcpdfFpdi;
use setasign\Fpdi\Tcpdf\Fpdi;



class DocumentoController extends Controller
{

    public function getPendientes()
    {
        // Obtener el cliente MongoDB
        $client = DB::connection('mongodb')->getMongoClient();

        // Obtener la lista de bases de datos
        $databases = $client->listDatabases();

        // Filtrar las colecciones que tienen el sufijo '_pendientes'
        $pendientesCollections = [];

        // Iterar sobre las bases de datos
        foreach ($databases as $database) {
            $db = $client->selectDatabase($database->getName());
            $collections = $db->listCollections(); // Obtener colecciones de cada base de datos

            // Iterar sobre las colecciones
            foreach ($collections as $collection) {
                $collectionName = $collection->getName();  // Obtener el nombre de la colección

                // Filtra las colecciones que terminan en '_pendientes'
                if (str_ends_with($collectionName, '_pendientes')) {
                    // Obtener los documentos de esa colección pendiente
                    $documents = DB::connection('mongodb')->collection($collectionName)->get();

                    // Agregar los documentos de esa colección al array
                    $pendientesCollections[] = [
                        'collection' => $collectionName,
                        'documents' => $documents
                    ];
                }
            }
        }

        // Retornar las colecciones pendientes
        return response()->json($pendientesCollections);
    }



    public function getDocumentbyid($plantillaName, $documentId)
    {
        // Obtiene un documento específico de la colección
        $document = DB::connection('mongodb')->collection($plantillaName)->find($documentId);

        if ($document) {
            return response()->json($document);
        } else {
            return response()->json(['error' => "Documento no encontrado"], 404);
        }
    }


    public function getforDocuments()
    {
        $collections = DB::connection('mongodb')->listCollections();

        $plantillas = [];

        foreach ($collections as $collection) {
            $plantillas[] = $collection->getName();
        }

        return response()->json($plantillas);
    }



    public function getAllDocuments($plantillaName)
    {
        // Verifica si la colección existe
        $collections = DB::connection('mongodb')->listCollections();
        $collectionExists = false;

        foreach ($collections as $collection) {
            if ($collection->getName() === $plantillaName) {
                $collectionExists = true;
                break;
            }
        }

        if ($collectionExists) {
            // Obtiene todos los documentos de la colección
            $documents = DB::connection('mongodb')->collection($plantillaName)->get();

            return response()->json($documents);
        } else {
            return response()->json(['error' => "La colección '{$plantillaName}' no existe."], 404);
        }
    }


    public function storeDocument(Request $request, $plantillaName)
    {
        $request->validate([
            'document_data' => 'required|array',
            'files.*' => 'nullable|file|mimes:pdf,jpg,png,mp4,mp3,wav,gif,tiff',
        ]);
    
        $documentData = $request->input('document_data');
        $uploadedFilesWatermarked = [];
        $uploadedFilesOriginal = [];
    
        if ($request->hasFile('files')) {
            @set_time_limit(0);
            $files = $request->file('files');
    
            foreach ($files as $file) {
                $guardado = $this->guardarArchivoConMarcaDeAgua($file);
                $uploadedFilesWatermarked[] = $guardado['watermarked'];
                $uploadedFilesOriginal[] = $guardado['original'];
            }
    

           // if (count($uploadedFilesWatermarked) > 10) {
             //   return response()->json(['error' => 'No puedes subir más de 10 archivos.'], 400);
           // }
    
            $documentData['Recurso Digital'] = $uploadedFilesWatermarked;
        }
    
        if (!is_array($documentData)) {
            $documentData = [$documentData];
        }
    
        $documentData['created_at'] = now();
        $documentData['updated_at'] = now();
    
        // Guardar en colección _pendientes (con marca de agua)
        $collectionPendientes = $plantillaName . '_pendientes';
        $documentId = DB::connection('mongodb')->collection($collectionPendientes)->insertGetId($documentData);
    
        // Guardar en colección _originales (con archivos originales)
        if (!empty($uploadedFilesOriginal)) {
            $originalData = $documentData;
            $originalData['Recurso Digital'] = $uploadedFilesOriginal;
    
            $collectionOriginales = $plantillaName . '_originales';
            DB::connection('mongodb')->collection($collectionOriginales)->insert($originalData);
        }
    
        return response()->json([
            'message' => 'Documento guardado con éxito',
            'id' => (string) $documentId,
            'files' => $uploadedFilesWatermarked,
        ], 201);
    }
    
private function agregarMarcaDeAgua($imagePath, $extension)
{
    $fontPath = public_path('fonts/alfa.ttf');
    if (!is_file($fontPath)) {
        return;
    }

    $image = null;

    if ($extension === 'jpg' || $extension === 'jpeg') {
        $image = @imagecreatefromjpeg($imagePath);
    } elseif ($extension === 'png') {
        $image = @imagecreatefrompng($imagePath);
    }

    if (!$image) {
        return;
    }

    if (function_exists('imagepalettetotruecolor')) {
        imagepalettetotruecolor($image);
    }

    $sprite = $this->spriteMarcaDeAgua($fontPath);
    if (!$sprite) {
        imagedestroy($image);
        return;
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $spriteW = imagesx($sprite);
    $spriteH = imagesy($sprite);

    imagealphablending($image, true);

    for ($y = -$spriteH; $y < $height; $y += 200) {
        for ($x = -$spriteW; $x < $width; $x += 200) {
            imagecopy($image, $sprite, $x, $y, 0, 0, $spriteW, $spriteH);
        }
    }

    if ($extension === 'jpg' || $extension === 'jpeg') {
        imagejpeg($image, $imagePath, 90);
    } elseif ($extension === 'png') {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagepng($image, $imagePath);
    }

    imagedestroy($image);
}

private function spriteMarcaDeAgua(string $fontPath)
{
    static $sprite = null;

    if ($sprite instanceof \GdImage) {
        return $sprite;
    }

    $text = 'AGQROO';
    $fontSize = 30;
    $angle = 45;
    $bbox = imagettfbbox($fontSize, $angle, $fontPath, $text);

    if ($bbox === false) {
        return null;
    }

    $xs = [$bbox[0], $bbox[2], $bbox[4], $bbox[6]];
    $ys = [$bbox[1], $bbox[3], $bbox[5], $bbox[7]];
    $minX = min($xs);
    $maxX = max($xs);
    $minY = min($ys);
    $maxY = max($ys);
    $pad = 4;
    $tw = (int) ceil($maxX - $minX) + ($pad * 2);
    $th = (int) ceil($maxY - $minY) + ($pad * 2);

    if ($tw < 1 || $th < 1) {
        return null;
    }

    $sprite = imagecreatetruecolor($tw, $th);
    imagealphablending($sprite, false);
    imagesavealpha($sprite, true);
    $transparent = imagecolorallocatealpha($sprite, 0, 0, 0, 127);
    imagefilledrectangle($sprite, 0, 0, $tw - 1, $th - 1, $transparent);
    imagealphablending($sprite, true);

    $color = imagecolorallocatealpha($sprite, 128, 0, 32, 75);
    imagettftext(
        $sprite,
        $fontSize,
        $angle,
        (int) ($pad - $minX),
        (int) ($pad - $minY),
        $color,
        $fontPath,
        $text
    );

    return $sprite;
}

private function agregarMarcaDeAguaPDF($pdfPath)
{
    $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('AGQROO');
    $pdf->SetTitle('Documento con Marca de Agua');
    $pdf->SetAutoPageBreak(false); // Evita saltos de página automáticos

    $pageCount = $pdf->setSourceFile($pdfPath);
    $outputPath = $pdfPath;

    // Configurar fuente y color
    $pdf->SetFont('Helvetica', 'B', 15); // Tamaño reducido
    $pdf->SetTextColor(128, 0, 32); // Rojo vino
    $text = 'AGQROO'; // Marca de agua

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($templateId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId);

        $pdf->SetAlpha(0.3); // Transparencia

        $textWidth = $pdf->GetStringWidth($text);
        $lineHeight = 10; // Altura entre líneas de texto

        // Espaciado para no saturar
        $stepX = $textWidth * 2;
        $stepY = $lineHeight * 2.5;

        for ($x = 0; $x < $size['width']; $x += $stepX) {
            for ($y = 0; $y < $size['height']; $y += $stepY) {
                $pdf->SetXY($x, $y);
                $pdf->Text($x, $y, $text);
            }
        }

        $pdf->SetAlpha(1); // Restaurar opacidad para siguientes elementos si se agregaran
    }

    $pdf->Output($outputPath, 'F');
}

private function guardarArchivoConMarcaDeAgua($file): array
{
    $extension = strtolower($file->getClientOriginalExtension());
    $uniqueName = uniqid();
    $filename = $uniqueName . '.' . $extension;
    $originalFilename = $uniqueName . '_original.' . $extension;

    $path = $file->storeAs('uploads', $filename, 'public');
    $fullPath = public_path("storage/{$path}");

    $originalPath = 'uploads/' . $originalFilename;
    $originalFullPath = public_path("storage/{$originalPath}");
    copy($fullPath, $originalFullPath);

    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
        $this->agregarMarcaDeAgua($fullPath, $extension);
    } elseif ($extension === 'pdf') {
        $this->agregarMarcaDeAguaPDF($fullPath);
    }

    return [
        'watermarked' => $path,
        'original' => $originalPath,
    ];
}

private function rutaArchivoOriginal($path): string
{
    $info = pathinfo($path);
    $dir = ($info['dirname'] === '.' || $info['dirname'] === '') ? '' : $info['dirname'] . '/';

    return $dir . $info['filename'] . '_original.' . ($info['extension'] ?? '');
}

private function agregarArchivosOriginales($plantillaName, $documento, $updateData, array $nuevosOriginales)
{
    if (!str_ends_with($plantillaName, '_pendientes') || empty($nuevosOriginales)) {
        return;
    }

    $collectionOriginales = str_replace('_pendientes', '_originales', $plantillaName);
    $originalDoc = null;

    $rutasOriginalesExistentes = [];
    if (!empty($documento['Recurso Digital']) && is_array($documento['Recurso Digital'])) {
        $rutasOriginalesExistentes = array_map([$this, 'rutaArchivoOriginal'], $documento['Recurso Digital']);
    }

    foreach ($rutasOriginalesExistentes as $rutaOriginal) {
        $originalDoc = DB::connection('mongodb')->collection($collectionOriginales)
            ->where('Recurso Digital', $rutaOriginal)
            ->first();

        if ($originalDoc) {
            break;
        }
    }

    if (!$originalDoc && isset($documento['created_at'])) {
        $originalDoc = DB::connection('mongodb')->collection($collectionOriginales)
            ->where('created_at', $documento['created_at'])
            ->first();
    }

    $originalUpdate = $updateData;
    unset($originalUpdate['Recurso Digital']);

    if ($originalDoc) {
        $archivosOriginales = $originalDoc['Recurso Digital'] ?? [];
        if (!is_array($archivosOriginales)) {
            $archivosOriginales = [];
        }

        foreach ($nuevosOriginales as $originalPath) {
            if (!in_array($originalPath, $archivosOriginales)) {
                $archivosOriginales[] = $originalPath;
            }
        }

        $originalUpdate['Recurso Digital'] = $archivosOriginales;

        DB::connection('mongodb')->collection($collectionOriginales)
            ->where('_id', $originalDoc['_id'])
            ->update($originalUpdate);

        return;
    }

    $originalData = $updateData;
    $originalData['Recurso Digital'] = $nuevosOriginales;
    $originalData['created_at'] = $documento['created_at'] ?? now();

    DB::connection('mongodb')->collection($collectionOriginales)->insert($originalData);
}





    public function deleteDocument($plantillaName, $documentId)
    {
        // Obtener el documento de la colección MongoDB
        $documento = DB::connection('mongodb')->collection($plantillaName)->where('_id', $documentId)->first();

        if ($documento) {
            // Verificar si el documento tiene un archivo asociado y eliminarlo
            if (isset($documento['Recurso Digital']) && is_array($documento['Recurso Digital'])) {
                foreach ($documento['Recurso Digital'] as $filePath) {
                    // Asegurarse de que el archivo no tiene el prefijo "uploads/"
                    if (strpos($filePath, 'uploads/') === 0) {
                        $filePath = substr($filePath, strlen('uploads/'));
                    }

                    // Obtener la ruta relativa correcta al archivo en el almacenamiento público
                    $relativePath = 'uploads/' . $filePath;

                    // Verificar si el archivo existe en el almacenamiento local
                    if (Storage::disk('public')->exists($relativePath)) {
                        // Intentar eliminar el archivo del almacenamiento local
                        try {
                            Storage::disk('public')->delete($relativePath);
                            Log::info('Archivo eliminado: ' . $relativePath);
                        } catch (\Exception $e) {
                            Log::error('Error al eliminar archivo: ' . $relativePath . '. Error: ' . $e->getMessage());
                        }
                    } else {
                        Log::warning('Archivo no encontrado en almacenamiento local: ' . $relativePath);
                    }
                }
            }

            // Eliminar el documento de la colección MongoDB
            $result = DB::connection('mongodb')->collection($plantillaName)->where('_id', $documentId)->delete();

            if ($result) {
                return response()->json(['message' => 'Documento y archivos asociados eliminados con éxito']);
            } else {
                return response()->json(['error' => 'Error al eliminar el documento'], 500);
            }
        } else {
            return response()->json(['error' => 'Documento no encontrado'], 404);
        }
    }

    public function updateDocument(Request $request, $plantillaName, $documentId)
{
    $request->validate([
        'files.*' => 'nullable|file|mimes:pdf,jpg,png,mp4,mp3,wav,gif,tiff|max:40480',
        'delete_files' => 'nullable|array',
        'existing_files' => 'nullable|array',
        'document_data' => 'nullable|array',
    ]);

    Log::info('Datos recibidos para actualizar:', $request->all());

    $documento = DB::connection('mongodb')->collection($plantillaName)->where('_id', $documentId)->first();

    if (!$documento) {
        return response()->json(['error' => 'Documento no encontrado'], 404);
    }

    $documento = is_array($documento) ? $documento : (array) $documento;
    $updateData = $this->extraerDatosActualizacion($request, $documento);
    $clavesDuplicadas = $this->clavesDuplicadasPorPhp($documento);

    // 🔹 Obtener archivos actuales desde `existing_files` si se envían
    $archivosActuales = $request->input('existing_files', []);

    // 🔹 Manejo de eliminación de archivos
    if ($request->has('delete_files') && isset($documento['Recurso Digital'])) {
        foreach ($request->input('delete_files') as $filePath) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
                Log::info("Archivo eliminado: $filePath");
            }

            $archivosActuales = array_values(array_diff($archivosActuales, [$filePath]));
        }
    }

    $uploadedFilesOriginal = [];

    if ($request->hasFile('files')) {
        @set_time_limit(0);

        foreach ($request->file('files') as $file) {
            $guardado = $this->guardarArchivoConMarcaDeAgua($file);

            if (!in_array($guardado['watermarked'], $archivosActuales)) {
                $archivosActuales[] = $guardado['watermarked'];
            }

            $uploadedFilesOriginal[] = $guardado['original'];
        }
    }

    unset($documento['Recurso_Digital']);

    $updateData['Recurso Digital'] = $archivosActuales;
    $updateData['updated_at'] = Carbon::now()->toDateTimeString();

    $query = DB::connection('mongodb')->collection($plantillaName)->where('_id', $documentId);
    $query->update($updateData);

    if (!empty($clavesDuplicadas)) {
        $query->drop($clavesDuplicadas);
    }

    if (!empty($uploadedFilesOriginal)) {
        $this->agregarArchivosOriginales($plantillaName, $documento, $updateData, $uploadedFilesOriginal);
    }

    return response()->json([
        'message' => 'Documento actualizado con éxito',
        'files' => $archivosActuales,
    ]);
}

private function extraerDatosActualizacion(Request $request, array $documento): array
{
    $incoming = $request->input('document_data');

    if (is_string($incoming)) {
        $incoming = json_decode($incoming, true) ?: [];
    }

    if (!is_array($incoming) || $incoming === []) {
        $incoming = $request->except([
            'files', 'delete_files', 'existing_files', '_id', 'document_data', '_coleccion'
        ]);
    }

    $protected = ['_id', 'created_at', 'Recurso Digital', 'Recurso_Digital', '_coleccion'];
    $updateData = [];

    foreach ($documento as $originalKey => $originalValue) {
        if (in_array($originalKey, $protected, true)) {
            continue;
        }

        $mangledKey = str_replace([' ', '.'], '_', $originalKey);

        if (array_key_exists($originalKey, $incoming)) {
            $updateData[$originalKey] = $incoming[$originalKey];
        } elseif (array_key_exists($mangledKey, $incoming)) {
            $updateData[$originalKey] = $incoming[$mangledKey];
        }
    }

    return $updateData;
}

private function clavesDuplicadasPorPhp(array $documento): array
{
    $duplicates = [];

    if (isset($documento['Recurso_Digital']) && isset($documento['Recurso Digital'])) {
        $duplicates[] = 'Recurso_Digital';
    }

    foreach ($documento as $key => $value) {
        $mangled = str_replace([' ', '.'], '_', $key);

        if ($mangled !== $key && array_key_exists($mangled, $documento)) {
            $duplicates[] = $mangled;
        }
    }

    return array_values(array_unique($duplicates));
}







public function obtenerUltimosDocumentos()
{
    $ultimosDocumentos = [];

    // Obtén todas las colecciones
    $colecciones = DB::connection('mongodb')->listCollections();

    foreach ($colecciones as $coleccion) {
        $nombreColeccion = $coleccion->getName();

        // Omitir colecciones específicas
        if (
            in_array($nombreColeccion, [
                'noticias_collection', 'carrousel_images', 'navbar_colors',
                'comentarios', 'migrations', 'users', 'personal_access_tokens',
                'plantillas_predeterminadas', 'visitas'
            ]) || str_ends_with($nombreColeccion, '_pendientes')
        ) {
            continue;
        }

        // Obtener el nombre del modelo dinámicamente basado en el nombre de la colección
        $modeloNombre = 'App\\Models\\' . ucfirst(Str::camel($nombreColeccion));

        if (class_exists($modeloNombre)) {
            $modelo = app($modeloNombre);

            // Obtener el valor de la propiedad estática $title utilizando reflexión
            $reflexion = new ReflectionClass($modelo);
            $title = $reflexion->getStaticPropertyValue('title', 'Título no definido');

            // Verificar si la colección tiene documentos
            $conteo = DB::connection('mongodb')->collection($nombreColeccion)->count();

            if ($conteo > 0) {
                // Obtener los últimos 4 documentos de la colección
                $documentos = DB::connection('mongodb')->collection($nombreColeccion)
                    ->orderBy('created_at', 'desc')
                    ->take(4)
                    ->get();

                // Agregar los documentos y el título a la lista final
                $ultimosDocumentos[$nombreColeccion] = [
                    'titulo' => $title,
                    'documentos' => $documentos->toArray()
                ];
            }
        }
    }

    Log::info('Enviado a Vue:', $ultimosDocumentos);

    return response()->json($ultimosDocumentos);
}



    public function approveDocument(Request $request, $collectionName, $documentId)
    {
        // Obtener el cliente MongoDB
        $client = DB::connection('mongodb')->getMongoClient();
        $nombreBaseDatos = 'proteo';  // El nombre de tu base de datos

        // Verificar que el documento ID es una cadena válida
        if (!$documentId) {
            return response()->json(['error' => 'El ID del documento es requerido.'], 400);
        }

        // Obtener el nombre de la colección definitiva sin el sufijo '_pendientes'
        $definitiveCollection = str_replace('_pendientes', '', $collectionName);

        // Buscar el documento en la colección '_pendientes'
        $document = DB::connection('mongodb')->collection($collectionName)->where('_id', $documentId)->first();

        // Si el documento no se encuentra
        if (!$document) {
            return response()->json(['error' => 'Documento no encontrado en la colección pendiente.'], 404);
        }

        // Eliminar el campo '_id' para evitar conflictos con MongoDB
        unset($document['_id']);  // MongoDB asignará un nuevo _id automáticamente

        // Insertar el documento en la colección definitiva (sin el sufijo '_pendientes')
        DB::connection('mongodb')->collection($definitiveCollection)->insert([$document]);

        // Eliminar el documento de la colección '_pendientes' usando delete()
        DB::connection('mongodb')->collection($collectionName)->where('_id', $documentId)->delete();

        return response()->json(['message' => 'Documento aprobado y movido a la colección definitiva.'], 200);
    }

     public function denyDocument($collectionName, $documentId)
{
    // Validar el ID
    if (!$documentId) {
        return response()->json(['error' => 'El ID del documento es requerido.'], 400);
    }

    // Verificar si el documento existe
    $document = DB::connection('mongodb')->collection($collectionName)->where('_id', $documentId)->first();

    if (!$document) {
        return response()->json(['error' => 'Documento no encontrado.'], 404);
    }

    // Eliminar el documento de la colección pendiente
    DB::connection('mongodb')->collection($collectionName)->where('_id', $documentId)->delete();

    return response()->json(['success' => true, 'message' => 'Documento denegado y eliminado correctamente.'], 200);
}
}
