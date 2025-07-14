<?php
declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageService implements ImageServiceInterface
{
    const UPLOADS_PATH = DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
    const ALLOWED_MIME_TYPES_MAP = [
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/webp' => '.webp',
        'image/gif' => '.gif',
        'image/svg+xml' => '.svg',
    ];

    public function moveImageToUploads(UploadedFile $fileInfo): ?string
    {
        if ($fileInfo->getError() === UPLOAD_ERR_NO_FILE)
        {
            return null;
        }

        $fileType = $fileInfo->getMimeType();
        $fileName = $fileInfo->getClientOriginalName();
        $imageExt = self::ALLOWED_MIME_TYPES_MAP[$fileType] ?? null;
        if (!$imageExt) 
        {
            throw new \InvalidArgumentException("File '$fileName' has non-image type '$fileType'");
        }
        $destFileName = uniqid('image', true) . $imageExt;
        return $this->moveFileToUploads($fileInfo, $destFileName);
    }

    private function getUploadPath(string $fileName): string
    {
        $uploadsPath = dirname(__DIR__, 2) . self::UPLOADS_PATH;
        if (!$uploadsPath || !is_dir($uploadsPath))
        {
            throw new \RuntimeException('Invalid uploads path: ' . self::UPLOADS_PATH);
        }
        return $uploadsPath . DIRECTORY_SEPARATOR . $fileName;
    }

    private function moveFileToUploads(UploadedFile $fileInfo, string $destFileName): string
    {
        $fileName = $fileInfo->getClientOriginalName();
        $destPath = $this->getUploadPath($destFileName);
        $srcPath = $fileInfo->getRealPath();
        if (!@move_uploaded_file($srcPath, $destPath)) 
        {
            throw new \RuntimeException("Failed to upload file $fileName");
        }
   
        return $destFileName;
    }

    public function deleteFileFromUploads(string $avatarPath): void
    {
        unlink($this->getUploadPath($avatarPath));
    }
}