<?php

namespace Modules\B2BContact\Services;

use Illuminate\Support\Facades\Storage;
use Modules\B2BContact\Models\B2BFiles;
use Symfony\Component\Process\Process;

class FileService
{
    public function scanVirus($file)
    {
        $tempPath = $file->getRealPath();

        // $scanCommand = 'wsl clamscan';
        $scanCommand = 'clamscan';
        
        $process = new Process([$scanCommand, $tempPath]);
        $process->setTimeout(200);
        $process->run();

        $virusFound = $process->getExitCode(); 
        
        // 0 = clean, 1 = infected
        return $virusFound;
    }

    public function uploadFile($contact_id = null, $files, $file_path)
    {
        $uploadedFiles = [];
        try {
            foreach ($files as $key => $file) {
                $file_name = uniqid() . '_' . $file->getClientOriginalName();
                
                // Store the file in MinIO
                //$file->storeAs('', $file_path, 'minio');
    
                // Store to local private storage
                $uploaded_file = Storage::disk('local')->putFileAs($file_path, $file, $file_name);

                $uploadedFiles[$key]['contact_id'] = $contact_id;        
                $uploadedFiles[$key]['file_name'] = $file_name;
                $uploadedFiles[$key]['file_path'] = $uploaded_file;
            }

            foreach ($uploadedFiles as $key => $file) {
                $createdFiles[] = B2BFiles::create($file);
            }
            return $createdFiles;

        } catch (\Exception $e) {
            return false;
        }
    }
}
