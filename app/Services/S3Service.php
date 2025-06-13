<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class S3Service
{
    protected static function initializeS3Client() {
        return new S3Client([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }
    /**
     * Upload a single file to S3 and return its URL.
     *
     * @param UploadedFile $file The file to upload.
     * @param string $path The path where the file should be stored.
     * @return string|null The URL of the uploaded file, or null if upload failed.
     */
    public static function uploadSingle(UploadedFile $file, string $path): ?string
    {
        if(env('APP_ENV') == 'local'){
            return self::localUploadSingle( $file,  $path);
        }else{
        $uploadedPath = $file->store($path, 's3');

        return $uploadedPath ? Storage::disk('s3')->url($uploadedPath) : null;
        }
    }

    /**
     * Upload multiple files to S3 and return their URLs.
     *
     * @param array $files Associative array of files with field names as keys.
     * @param array $paths Associative array of paths with field names as keys.
     * @return array Associative array of URLs of the uploaded files.
     */
    public static function uploadMultiple(array $files, string $path): array
    {
        $uploadedUrls = [];  // Array to hold paths of uploaded images
    
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                // Store each file in the specified S3 path
                $uploadedPath = $file->store($path, 's3');
                
                // Get the full URL of the uploaded file
                $uploadedUrls[] = $uploadedPath ? Storage::disk('s3')->url($uploadedPath) : null;
            }
        }
    
        return $uploadedUrls;  // Return array of image URLs
    }
    


    public static function deleteFile($filePath) {
        try {
            $s3Client = self::initializeS3Client();
            $bucket = env('AWS_BUCKET');

            // Convert the file path to the relative path needed by S3
            $relativePath = str_replace("https://{$bucket}.s3.amazonaws.com/", '', $filePath);

            // Delete the single file
            $s3Client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $relativePath,
            ]);

            return true;
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            Log::error("Error deleting file from S3: " . $e->getMessage());
            return false;
        }
    }
     public static function localUploadSingle(UploadedFile $file, string $path): ?string
    {
        try {
            // Create the full path in the public directory
            $destinationPath = public_path($path);
            
            // Ensure the directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Generate a unique name for the file
            $fileName = uniqid() . '_' . $file->getClientOriginalName();

            // Move the file to the public directory
            $file->move($destinationPath, $fileName);

           if(env('APP_ENV') == 'local'){
             // Return the public URL for the uploaded file
             return asset("/$path/$fileName");
           }else{
             // Return the public URL for the uploaded file
             return asset("/$path/$fileName");
           }
        } catch (\Exception $e) {
            // Log any errors
            Log::error("Error uploading file: " . $e->getMessage());
            return null;
        }
    }


}
