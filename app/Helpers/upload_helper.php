<?php

if (! function_exists('handleImageUpload')) {
    /**
     * @return array{success: bool, filename?: string, error?: string}
     */
    function handleImageUpload(string $fileKey, string $destination = 'uploads/payments'): array
    {
        $request = \Config\Services::request();
        $file    = $request->getFile($fileKey);

        if (! $file || ! $file->isValid()) {
            return ['success' => false, 'error' => 'File tidak valid.'];
        }

        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        if (! in_array($file->getMimeType(), $allowed)) {
            return ['success' => false, 'error' => 'Hanya JPG dan PNG yang diperbolehkan.'];
        }

        if ($file->getSize() / 1024 > 2048) {
            return ['success' => false, 'error' => 'Ukuran file maksimum 2 MB.'];
        }

        $newName = $file->getRandomName();
        $path    = FCPATH . trim($destination, '/');

        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }

        $file->move($path, $newName);

        return ['success' => true, 'filename' => $newName];
    }
}

