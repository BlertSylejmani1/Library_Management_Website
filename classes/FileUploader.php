<?php

class FileUploader
{
    public static function uploadBookCover(array $file, ?string $existingFile = null): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existingFile;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Book cover upload failed.');
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $tmp = $file['tmp_name'] ?? '';
        $mime = is_file($tmp) ? mime_content_type($tmp) : '';
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Only JPG, PNG, and WEBP cover images are allowed.');
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new RuntimeException('Cover image must be smaller than 2MB.');
        }

        if (!is_dir(UPLOAD_BOOK_DIR)) {
            mkdir(UPLOAD_BOOK_DIR, 0775, true);
        }

        $filename = uniqid('book_', true) . '.' . $allowed[$mime];
        $destination = UPLOAD_BOOK_DIR . '/' . $filename;

        if (!move_uploaded_file($tmp, $destination)) {
            throw new RuntimeException('Unable to save the uploaded cover image.');
        }

        if ($existingFile) {
            self::deleteBookCover($existingFile);
        }

        return $filename;
    }

    public static function deleteBookCover(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $path = UPLOAD_BOOK_DIR . '/' . basename($filename);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
