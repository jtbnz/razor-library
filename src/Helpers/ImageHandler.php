<?php
/**
 * Image handling utilities
 */

class ImageHandler
{
    /**
     * Process and save an uploaded image
     *
     * @param array $file $_FILES array element
     * @param string $subPath Path within uploads (e.g., "users/1/razors")
     * @return array|false Returns ['filename' => ..., 'thumb' => ...] or false on failure
     */
    public static function processUpload(array $file, string $subPath): array|false
    {
        // Validate upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Check file size
        if ($file['size'] > config('UPLOAD_MAX_SIZE')) {
            return false;
        }

        // Check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, config('ALLOWED_IMAGE_TYPES'))) {
            return false;
        }

        // Check if this format is supported by the GD library
        if (!self::isFormatSupported($mimeType)) {
            return false;
        }

        // Generate UUID filename
        $uuid = generate_uuid();
        $extension = mime_to_extension($mimeType);
        $filename = $uuid . '.' . $extension;
        $thumbFilename = $uuid . '_thumb.' . $extension;

        // Create directory if needed
        $uploadPath = config('UPLOAD_PATH') . '/' . $subPath;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fullPath = $uploadPath . '/' . $filename;
        $thumbPath = $uploadPath . '/' . $thumbFilename;

        // Load and process image
        $image = self::loadImage($file['tmp_name'], $mimeType);
        if (!$image) {
            return false;
        }

        // Resize main image if needed
        $image = self::resizeImage($image, config('IMAGE_MAX_DIMENSION'));

        // Save main image
        if (!self::saveImage($image, $fullPath, $mimeType)) {
            imagedestroy($image);
            return false;
        }

        // Create and save thumbnail
        $thumb = self::resizeImage($image, config('THUMBNAIL_SIZE'));
        self::saveImage($thumb, $thumbPath, $mimeType);
        imagedestroy($thumb);

        imagedestroy($image);

        return [
            'filename' => $filename,
            'thumb' => $thumbFilename,
            'path' => $subPath . '/' . $filename,
            'thumb_path' => $subPath . '/' . $thumbFilename,
        ];
    }

    /**
     * Load an image from file
     */
    private static function loadImage(string $path, string $mimeType): \GdImage|false
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    /**
     * Resize an image to fit within max dimension
     */
    private static function resizeImage(\GdImage $image, int $maxDimension): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // Check if resize is needed
        if ($width <= $maxDimension && $height <= $maxDimension) {
            // Return a copy
            $copy = imagecreatetruecolor($width, $height);
            self::preserveTransparency($copy, $image);
            imagecopy($copy, $image, 0, 0, 0, 0, $width, $height);
            return $copy;
        }

        // Calculate new dimensions
        if ($width > $height) {
            $newWidth = $maxDimension;
            $newHeight = (int) ($height * ($maxDimension / $width));
        } else {
            $newHeight = $maxDimension;
            $newWidth = (int) ($width * ($maxDimension / $height));
        }

        // Create resized image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        self::preserveTransparency($resized, $image);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $resized;
    }

    /**
     * Preserve transparency for PNG and GIF
     */
    private static function preserveTransparency(\GdImage $dest, \GdImage $src): void
    {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefilledrectangle($dest, 0, 0, imagesx($dest), imagesy($dest), $transparent);
        imagealphablending($dest, true);
    }

    /**
     * Save an image to file
     */
    private static function saveImage(\GdImage $image, string $path, string $mimeType): bool
    {
        return match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $path, 85),
            'image/png' => imagepng($image, $path, 8),
            'image/gif' => imagegif($image, $path),
            'image/webp' => imagewebp($image, $path, 85),
            default => false,
        };
    }

    /**
     * Delete an image and its thumbnail
     */
    public static function delete(string $path): bool
    {
        $uploadPath = config('UPLOAD_PATH');
        $fullPath = $uploadPath . '/' . $path;

        // Delete main image
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Delete thumbnail
        $thumbPath = preg_replace('/\.([^.]+)$/', '_thumb.$1', $fullPath);
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }

        return true;
    }

    /**
     * Get the full filesystem path for an upload
     */
    public static function getFullPath(string $path): string
    {
        return config('UPLOAD_PATH') . '/' . $path;
    }

    /**
     * Check if an uploaded file exists
     */
    public static function exists(string $path): bool
    {
        return file_exists(self::getFullPath($path));
    }

    /**
     * Upload an image with detailed error reporting
     *
     * @param array $file $_FILES array element
     * @param string $subPath Path within uploads (e.g., "users/1/razors")
     * @return array Returns ['success' => bool, 'filename' => string|null, 'error' => string|null]
     */
    public static function upload(array $file, string $subPath): array
    {
        // Validate upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            ];
            return [
                'success' => false,
                'filename' => null,
                'error' => $errors[$file['error']] ?? 'Unknown upload error',
            ];
        }

        // Check file size
        if ($file['size'] > config('UPLOAD_MAX_SIZE')) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'File too large (max ' . (config('UPLOAD_MAX_SIZE') / 1024 / 1024) . 'MB)',
            ];
        }

        // Check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, config('ALLOWED_IMAGE_TYPES'))) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Invalid file type. Allowed: JPEG, PNG, GIF' . (self::isFormatSupported('image/webp') ? ', WebP' : ''),
            ];
        }

        // Check if this format is supported by GD
        if (!self::isFormatSupported($mimeType)) {
            $formatName = strtoupper(str_replace('image/', '', $mimeType));
            return [
                'success' => false,
                'filename' => null,
                'error' => "{$formatName} format not supported by this server",
            ];
        }

        // Process the upload
        $result = self::processUpload($file, $subPath);

        if ($result === false) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Failed to process image',
            ];
        }

        return [
            'success' => true,
            'filename' => $result['filename'],
            'error' => null,
        ];
    }

    /**
     * Check if a given image format is supported by GD
     */
    public static function isFormatSupported(string $mimeType): bool
    {
        // Get GD info
        $gdInfo = gd_info();

        return match ($mimeType) {
            'image/jpeg' => isset($gdInfo['JPEG Support']) && $gdInfo['JPEG Support'],
            'image/png' => isset($gdInfo['PNG Support']) && $gdInfo['PNG Support'],
            'image/gif' => isset($gdInfo['GIF Read Support']) && $gdInfo['GIF Read Support'],
            'image/webp' => isset($gdInfo['WebP Support']) && $gdInfo['WebP Support'],
            default => false,
        };
    }

    /**
     * Get list of supported image formats for display to users
     */
    public static function getSupportedFormats(): array
    {
        $formats = [];
        $gdInfo = gd_info();

        if (isset($gdInfo['JPEG Support']) && $gdInfo['JPEG Support']) {
            $formats[] = 'JPEG';
        }
        if (isset($gdInfo['PNG Support']) && $gdInfo['PNG Support']) {
            $formats[] = 'PNG';
        }
        if (isset($gdInfo['GIF Read Support']) && $gdInfo['GIF Read Support']) {
            $formats[] = 'GIF';
        }
        if (isset($gdInfo['WebP Support']) && $gdInfo['WebP Support']) {
            $formats[] = 'WebP';
        }

        return $formats;
    }
}
