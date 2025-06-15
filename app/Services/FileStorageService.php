<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class FileStorageService
{
    protected string $storageDisk;

    public function __construct()
    {
        $this->storageDisk = 'public'; // Explicitly use 'public' disk
        $this->storageDisk = 'public_direct'; // Using the new direct public disk
    }

    public function store(UploadedFile $file, string $directory): string|false
    {
        try {
            $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $filename, $this->storageDisk);
            if (!$path) {
                Log::error("FileStorageService: Failed to store file in '{$directory}' on disk '{$this->storageDisk}'.");
                return false;
            }
            return $path;
        } catch (Exception $e) {
            Log::error("FileStorageService: Exception during file store.", ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function url(?string $path): ?string
    {
        if (empty($path)) return null;
        try {
            return Storage::disk($this->storageDisk)->url($path);
        } catch (Exception $e) {
            Log::error("FileStorageService: Exception getting URL for path '{$path}'.", ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function delete(?string $path): bool
    {
        if (empty($path)) return true;
        try {
            if (Storage::disk($this->storageDisk)->exists($path)) {
                return Storage::disk($this->storageDisk)->delete($path);
            }
            return true;
        } catch (Exception $e) {
            Log::error("FileStorageService: Exception during file delete for '{$path}'.", ['message' => $e->getMessage()]);
            return false;
        }
    }
}
