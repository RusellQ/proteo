<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use ZipArchive;


class RespaldoController extends Controller
{
    public function createBackup()
{
    $backupPath = storage_path('app/backups');

    if (!file_exists($backupPath)) {
        mkdir($backupPath, 0755, true);
    }

    $date = now()->format('Y-m-d_H-i-s');
    $mongoBackupFile = "$backupPath/database_$date.gz";
    $zipFile = "$backupPath/backup_$date.zip";

    // 1️⃣ Respaldar MongoDB
    $mongoCommand = "mongodump --uri=mongodb://localhost:27017/proteo --archive=$mongoBackupFile --gzip";
    exec($mongoCommand . ' 2>&1', $mongoOutput, $mongoReturnCode);

    if ($mongoReturnCode !== 0) {
        return response()->json([
            'message' => 'Error al respaldar MongoDB',
            'output' => $mongoOutput
        ], 500);
    }

    // 2️⃣ Crear ZIP
    $zip = new \ZipArchive();
    if ($zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {

        // Agregar dump Mongo
        $zip->addFile($mongoBackupFile, "database_$date.gz");

        // 3️⃣ Agregar carpeta uploads
        $uploadsPath = public_path('uploads');

        if (file_exists($uploadsPath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($uploadsPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'uploads/' . substr($filePath, strlen($uploadsPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        $zip->close();

        // Opcional: borrar dump suelto
        unlink($mongoBackupFile);

        return response()->json([
            'message' => 'Respaldo completo creado con éxito',
            'file' => $zipFile
        ]);
    }

    return response()->json(['message' => 'Error al crear el ZIP'], 500);
}



    public function fechaUltimoRespaldo()
    {
        $backupPath = storage_path('app/backups');
        $latestBackup = collect(File::files($backupPath))->sortByDesc('mtime')->last();

        return response()->json([
            'lastBackupDate' => $latestBackup ? date('Y-m-d H:i:s', $latestBackup->getMTime()) : null,
        ], $latestBackup ? 200 : 404);
    }

    public function listaRespaldos()
    {
        $backupPath = storage_path('app/backups');
        $backupFiles = File::files($backupPath);
        $backupList = [];

        foreach ($backupFiles as $file) {
            $backupList[] = [
                'filename' => $file->getFilename(),
                'backupDate' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        return response()->json(['backupList' => $backupList]);
    }

    public function abrirApp()
    {
        $backupFolderPath = 'C:\\laragon\\www\\proteo\\storage\\app\\backups';

        // Intentar abrir la carpeta
        try {
            exec("start explorer \"$backupFolderPath\"");
            return response()->json(['message' => 'Carpeta abierta con éxito']);
        } catch (\Exception $e) {
            Log::error('Error al abrir la carpeta de respaldos: ' . $e->getMessage());
            return response()->json(['message' => 'Error al abrir la carpeta de respaldos'], 500);
        }
    }

    public function restore(Request $request)
{
    if ($request->hasFile('backupFile')) {
        $file = $request->file('backupFile');
        $filePath = $file->getRealPath();
        $extractPath = storage_path('app/backups/extracted');

        // Crear directorio para extraer el archivo gz
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        // Registrar el tamaño y tipo del archivo subido   
        Log::info('Archivo subido', [
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        // Verificar que el archivo sea un archivo .gz
        if ($file->getClientMimeType() !== 'application/gzip' && $file->getClientMimeType() !== 'application/x-gzip') {
            Log::error('El archivo cargado no es un archivo GZ válido', [
                'mimeType' => $file->getClientMimeType()
            ]);
            return response()->json(['message' => 'El archivo cargado no es un archivo GZ válido'], 400);
        }

        // Extraer el archivo .gz
        $gzFilePath = $extractPath . '/' . $file->getClientOriginalName();
        file_put_contents($gzFilePath, file_get_contents($filePath));

        // Comando para ejecutar el restore
        $command = "mongorestore --uri=mongodb://localhost:27017/proteo --gzip --archive=$gzFilePath";

        // Ejecutar el comando y capturar el código de retorno
        $returnCode = null;
        $output = null;
        exec($command, $output, $returnCode);

        // Log del resultado del comando
        Log::info('Comando de restauración ejecutado con código de retorno: ' . $returnCode);

        // Verificar si el comando se ejecutó con éxito
        if ($returnCode === 0) {
            return response()->json(['message' => 'Restauración completada con éxito']);
        } else {
            Log::error('Error al ejecutar el comando de restauración: ' . implode("\n", $output));
            return response()->json(['message' => 'Error al restaurar el respaldo', 'output' => $output], 500);
        }
    } else {
        return response()->json(['message' => 'No se ha proporcionado ningún archivo de respaldo'], 400);
    }
}



}
