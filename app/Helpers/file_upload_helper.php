<?php

/**
 * File Upload Helper
 *
 * Helper functions for file upload and validation
 */

if (!function_exists('upload_chat_file')) {
    /**
     * Upload chat file (image or document)
     *
     * @param \CodeIgniter\HTTP\Files\UploadedFile $file
     * @param int                                   $employeeId
     * @return array ['success' => bool, 'file_path' => string, 'file_name' => string, 'file_size' => int, 'file_type' => string, 'message' => string]
     */
    function upload_chat_file($file, int $employeeId): array
    {
        // Validate file
        if (!$file->isValid()) {
            return [
                'success'   => false,
                'message'   => 'Arquivo inválido.',
                'file_path' => null,
                'file_name' => null,
                'file_size' => null,
                'file_type' => null,
            ];
        }

        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if ($file->getSize() > $maxSize) {
            return [
                'success'   => false,
                'message'   => 'Arquivo muito grande. Tamanho máximo: 10MB.',
                'file_path' => null,
                'file_name' => null,
                'file_size' => null,
                'file_type' => null,
            ];
        }

        // Get file extension and MIME type
        $extension = $file->getClientExtension();
        $mimeType = $file->getClientMimeType();

        // Validate file type
        $allowedTypes = get_allowed_chat_file_types();
        $isAllowed = false;
        $fileType = null;

        foreach ($allowedTypes as $type => $config) {
            if (in_array($extension, $config['extensions']) || in_array($mimeType, $config['mimes'])) {
                $isAllowed = true;
                $fileType = $type;

                break;
            }
        }

        if (!$isAllowed) {
            return [
                'success'   => false,
                'message'   => 'Tipo de arquivo não permitido.',
                'file_path' => null,
                'file_name' => null,
                'file_size' => null,
                'file_type' => null,
            ];
        }

        // Generate unique filename
        $originalName = $file->getClientName();
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $newName = $safeName . '_' . time() . '_' . uniqid() . '.' . $extension;

        // Create directory structure: uploads/chat/{year}/{month}/{employee_id}
        $uploadPath = WRITEPATH . 'uploads/chat/' . date('Y') . '/' . date('m') . '/' . $employeeId;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Move file
        try {
            $file->move($uploadPath, $newName);

            $relativePath = 'uploads/chat/' . date('Y') . '/' . date('m') . '/' . $employeeId . '/' . $newName;

            return [
                'success'   => true,
                'message'   => 'Arquivo enviado com sucesso.',
                'file_path' => $relativePath,
                'file_name' => $originalName,
                'file_size' => $file->getSize(),
                'file_type' => $fileType,
            ];
        } catch (\Exception $e) {
            log_message('error', 'File upload failed: ' . $e->getMessage());

            return [
                'success'   => false,
                'message'   => 'Erro ao fazer upload do arquivo.',
                'file_path' => null,
                'file_name' => null,
                'file_size' => null,
                'file_type' => null,
            ];
        }
    }
}

if (!function_exists('get_allowed_chat_file_types')) {
    /**
     * Get allowed file types for chat
     *
     * @return array
     */
    function get_allowed_chat_file_types(): array
    {
        return [
            'image' => [
                'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'mimes'      => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            ],
            'document' => [
                'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'],
                'mimes'      => [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain',
                    'text/csv',
                ],
            ],
            'archive' => [
                'extensions' => ['zip', 'rar', '7z'],
                'mimes'      => [
                    'application/zip',
                    'application/x-rar-compressed',
                    'application/x-7z-compressed',
                ],
            ],
        ];
    }
}

if (!function_exists('delete_chat_file')) {
    /**
     * Delete chat file
     *
     * @param string $filePath
     * @return bool
     */
    function delete_chat_file(string $filePath): bool
    {
        $fullPath = WRITEPATH . $filePath;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }
}

if (!function_exists('get_file_icon')) {
    /**
     * Get Font Awesome icon for file type
     *
     * @param string $fileType
     * @param string $extension
     * @return string
     */
    function get_file_icon(string $fileType, string $extension = ''): string
    {
        $icons = [
            'image'    => 'fa-file-image',
            'document' => 'fa-file-alt',
            'archive'  => 'fa-file-archive',
        ];

        // Specific extensions
        $specificIcons = [
            'pdf'  => 'fa-file-pdf',
            'doc'  => 'fa-file-word',
            'docx' => 'fa-file-word',
            'xls'  => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'txt'  => 'fa-file-alt',
            'csv'  => 'fa-file-csv',
            'zip'  => 'fa-file-archive',
            'rar'  => 'fa-file-archive',
        ];

        if ($extension && isset($specificIcons[$extension])) {
            return $specificIcons[$extension];
        }

        return $icons[$fileType] ?? 'fa-file';
    }
}

if (!function_exists('format_file_size')) {
    /**
     * Format file size in human-readable format
     *
     * @param int $bytes
     * @return string
     */
    function format_file_size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}

if (!function_exists('is_image_file')) {
    /**
     * Check if file is an image
     *
     * @param string $fileType
     * @return bool
     */
    function is_image_file(string $fileType): bool
    {
        return $fileType === 'image';
    }
}

if (!function_exists('get_file_url')) {
    /**
     * Get public URL for file
     *
     * @param string $filePath
     * @return string
     */
    function get_file_url(string $filePath): string
    {
        return base_url('chat/file/download?path=' . urlencode($filePath));
    }
}

if (!function_exists('validate_file_access')) {
    /**
     * Validate if employee has access to file
     *
     * @param string $filePath
     * @param int    $employeeId
     * @return bool
     */
    function validate_file_access(string $filePath, int $employeeId): bool
    {
        // Extract employee ID from path
        // Format: uploads/chat/{year}/{month}/{employee_id}/{filename}
        $parts = explode('/', $filePath);

        if (count($parts) >= 5) {
            $fileEmployeeId = (int) $parts[4];

            // Employee can access their own files
            if ($fileEmployeeId === $employeeId) {
                return true;
            }

            // Check if employee is admin or in the same room
            $db = \Config\Database::connect();

            // Find message with this file
            $message = $db->table('chat_messages')
                ->where('file_path', $filePath)
                ->get()
                ->getRow();

            if ($message) {
                // Check if employee is member of the room
                $isMember = $db->table('chat_room_members')
                    ->where('room_id', $message->room_id)
                    ->where('employee_id', $employeeId)
                    ->countAllResults() > 0;

                return $isMember;
            }
        }

        return false;
    }
}

if (!function_exists('sanitize_filename')) {
    /**
     * Sanitize filename for safe storage
     *
     * @param string $filename
     * @return string
     */
    function sanitize_filename(string $filename): string
    {
        // Remove directory traversal attempts
        $filename = basename($filename);

        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Limit length
        if (strlen($filename) > 200) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 190);
            $filename = $name . '.' . $ext;
        }

        return $filename;
    }
}
