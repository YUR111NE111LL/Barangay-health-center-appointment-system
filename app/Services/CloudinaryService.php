<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    /**
     * Upload an image to Cloudinary
     */
    public static function uploadImage(UploadedFile $file, string $folder = 'uploads', array $options = []): ?array
    {
        try {
            // Get Cloudinary instance
            $cloudinary = app(\Cloudinary\Cloudinary::class);

            // Build upload options
            $uploadOptions = [
                'folder' => $folder,
            ];

            // Handle transformations if provided
            if (isset($options['transformation']) && is_array($options['transformation'])) {
                $transformation = $options['transformation'];
                $transformationArray = [];

                if (isset($transformation['width'])) {
                    $transformationArray['width'] = $transformation['width'];
                }
                if (isset($transformation['height'])) {
                    $transformationArray['height'] = $transformation['height'];
                }
                if (isset($transformation['crop'])) {
                    $transformationArray['crop'] = $transformation['crop'];
                }
                if (isset($transformation['gravity'])) {
                    $transformationArray['gravity'] = $transformation['gravity'];
                }
                if (isset($transformation['quality'])) {
                    $transformationArray['quality'] = $transformation['quality'];
                }
                if (isset($transformation['format'])) {
                    $transformationArray['format'] = $transformation['format'];
                }

                if (! empty($transformationArray)) {
                    $uploadOptions['transformation'] = [$transformationArray];
                }
            }

            // Upload to Cloudinary using uploadApi
            $result = $cloudinary->uploadApi()->upload($file->getRealPath(), $uploadOptions);

            // Return the result data
            return [
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url'],
                'url' => $result['url'],
                'format' => $result['format'],
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'bytes' => $result['bytes'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed: '.$e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'folder' => $folder,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Upload a video to Cloudinary
     */
    public static function uploadVideo(UploadedFile $file, string $folder = 'videos', array $options = []): ?array
    {
        try {
            $cloudinary = app(\Cloudinary\Cloudinary::class);

            $uploadOptions = [
                'folder' => $folder,
                'resource_type' => 'video',
            ];

            $uploadOptions = array_merge($uploadOptions, $options);

            $result = $cloudinary->uploadApi()->upload($file->getRealPath(), $uploadOptions);

            return [
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url'],
                'url' => $result['url'],
                'format' => $result['format'],
                'duration' => $result['duration'] ?? null,
                'bytes' => $result['bytes'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary video upload failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Delete a file from Cloudinary
     */
    public static function delete(string $publicId, string $resourceType = 'image'): bool
    {
        try {
            $cloudinary = app(\Cloudinary\Cloudinary::class);
            $result = $cloudinary->uploadApi()->destroy($publicId, ['resource_type' => $resourceType]);

            return ($result['result'] ?? '') === 'ok';
        } catch (\Exception $e) {
            Log::error('Cloudinary delete failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get optimized image URL with transformations
     */
    public static function getOptimizedUrl(string $publicId, array $transformations = []): string
    {
        $cloudinary = app(\Cloudinary\Cloudinary::class);
        $defaultTransformations = [
            'quality' => 'auto',
            'fetch_format' => 'auto',
        ];

        $transformations = array_merge($defaultTransformations, $transformations);

        return $cloudinary->image($publicId)->toUrl($transformations);
    }
}
