<?php

namespace G8Key\Client\Stores;

use G8Key\Client\Contracts\LicenseStore;
use RuntimeException;

class FileLicenseStore implements LicenseStore
{
    public function __construct(private readonly string $path) {}

    public function read(): ?array
    {
        if (! is_file($this->path)) {
            return null;
        }

        $contents = file_get_contents($this->path);

        if ($contents === false) {
            return null;
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function write(array $data): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException("Cannot create directory: {$directory}");
        }

        $bytes = file_put_contents(
            $this->path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX,
        );

        if ($bytes === false) {
            throw new RuntimeException("Cannot write license cache: {$this->path}");
        }

        @chmod($this->path, 0600);
    }

    public function clear(): void
    {
        if (is_file($this->path)) {
            @unlink($this->path);
        }
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }
}
