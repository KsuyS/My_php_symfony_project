<?php
namespace App\Service;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ImageServiceInterface
{
    public function moveImageToUploads(UploadedFile $fileInfo): ?string;
    public function deleteFileFromUploads(string $avatarPath): void;
}