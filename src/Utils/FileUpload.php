<?php

namespace InstagramClone\Utils;

use InstagramClone\Config\Config;
use Intervention\Image\ImageManagerStatic as Image;

class FileUpload
{
    private $uploadPath;
    private $maxFileSize;
    private $allowedExtensions;

    public function __construct()
    {
        $this->uploadPath = Config::getUploadPath();
        $this->maxFileSize = Config::getMaxFileSize();
        $this->allowedExtensions = Config::getAllowedExtensions();

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    public function uploadImage(array $file): array
    {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }

        try {
            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $this->generateUniqueFilename($extension);
            $filePath = $this->uploadPath . $filename;

            // Process and save image
            $image = Image::make($file['tmp_name']);
            
            // Resize to Instagram standard size (1080x1080 for square)
            $image->fit(1080, 1080, function ($constraint) {
                $constraint->upsize();
            });

            // Compress and save
            $image->save($filePath, 85);

            // Create thumbnail
            $this->createThumbnail($image, $filename);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filePath,
                'url' => Config::getAppUrl() . '/' . $this->uploadPath . $filename
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to process image: ' . $e->getMessage()
            ];
        }
    }

    public function uploadProfilePicture(array $file): array
    {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }

        try {
            // Generate unique filename with prefix
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'profile_' . $this->generateUniqueFilename($extension);
            $filePath = $this->uploadPath . $filename;

            // Process and save image
            $image = Image::make($file['tmp_name']);
            
            // Resize to profile picture size (150x150)
            $image->fit(150, 150);

            // Compress and save
            $image->save($filePath, 90);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filePath,
                'url' => Config::getAppUrl() . '/' . $this->uploadPath . $filename
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to process profile picture: ' . $e->getMessage()
            ];
        }
    }

    private function validateFile(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'message' => 'File upload error: ' . $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / 1024 / 1024, 2);
            return [
                'valid' => false,
                'message' => "File size exceeds maximum allowed size of {$maxSizeMB}MB"
            ];
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $allowedStr = implode(', ', $this->allowedExtensions);
            return [
                'valid' => false,
                'message' => "Invalid file type. Allowed types: {$allowedStr}"
            ];
        }

        // Check if it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            return [
                'valid' => false,
                'message' => 'File is not a valid image'
            ];
        }

        return ['valid' => true];
    }

    private function generateUniqueFilename(string $extension): string
    {
        return uniqid() . '_' . time() . '.' . $extension;
    }

    private function createThumbnail($image, string $filename): void
    {
        $thumbnailPath = $this->uploadPath . 'thumb_' . $filename;
        
        // Create a copy for thumbnail
        $thumbnail = clone $image;
        $thumbnail->fit(320, 320);
        $thumbnail->save($thumbnailPath, 80);
    }

    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    public function deleteFile(string $filename): bool
    {
        $filePath = $this->uploadPath . $filename;
        $thumbnailPath = $this->uploadPath . 'thumb_' . $filename;

        $success = true;
        
        if (file_exists($filePath)) {
            $success = unlink($filePath) && $success;
        }
        
        if (file_exists($thumbnailPath)) {
            $success = unlink($thumbnailPath) && $success;
        }

        return $success;
    }
}