<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Support;

use Einvoicing\Invoice;
use Einvoicing\Readers\UblReader;
use Pristavu\Anaf\Contracts\Arrayable;
use RuntimeException;
use ZipArchive;

/**
 * Extract XML invoice and signature from a zip archive.
 */
class Extract implements Arrayable
{
    public string $archive;

    public string $xmlInvoice;

    public string $xmlSignature;

    /**
     * Create an Extract instance using a zip archive path or zip content (zip bytes or base64-encoded zip).
     *
     * @throws RuntimeException
     */
    public function __construct(string $archive)
    {

        $bytes = null;

        if ($this->isReadablePath($archive)) {
            $bytes = file_get_contents($archive);
        } elseif ($this->isBase64Zip($archive)) {
            $bytes = base64_decode($archive, true);
        } elseif ($this->isRawZip($archive)) {
            $bytes = $archive;
        }

        if (! is_string($bytes) || $bytes === '' || ! $this->isRawZip($bytes)) {
            throw new RuntimeException('Archive must be a readable file path, raw ZIP bytes, or base64-encoded ZIP.');
        }

        $this->archive = $bytes;
        $this->unzip();
    }

    /**
     * Create an Extract instance using a zip archive path or zip content (zip bytes or base64-encoded zip).
     *
     * @throws RuntimeException
     */
    public static function from(string $archive): self
    {
        return new self($archive);
    }

    /**
     * Get the XML invoice extracted content.
     */
    public function xmlInvoice(): string
    {
        return $this->xmlInvoice;
    }

    /**
     * Get the DTO invoice object parsed from the XML content.
     */
    public function dtoInvoice(): Invoice
    {
        $reader = new UblReader();

        return $reader->import($this->xmlInvoice());
    }

    /**
     * Get the XML signature extracted content.
     */
    public function signature(): string
    {
        return $this->xmlSignature;
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'xmlInvoice' => $this->xmlInvoice(),
            'dtoInvoice' => $this->dtoInvoice(),
            'signature' => $this->signature(),
        ];
    }

    /**
     * Unzip the archive and extract XML invoice and signature.
     */
    private function unzip(): void
    {

        $tmp = tmpfile();
        fwrite($tmp, $this->archive);

        $zip = new ZipArchive();
        $zip->open(stream_get_meta_data($tmp)['uri']);

        collect(range(0, $zip->numFiles - 1))
            ->map(fn (int $index): string|false => $zip->getNameIndex($index))
            ->each(function (string $name) use ($zip): void {
                if (str_starts_with($name, 'semnatura_')) {
                    $this->xmlSignature = $zip->getFromName($name);
                } else {
                    $this->xmlInvoice = $zip->getFromName($name);
                }
            });

        $zip->close();
        fclose($tmp);

    }

    /**
     * Check if the given string is a readable file path.
     */
    private function isReadablePath(string $path): bool
    {
        // Limit length to avoid misclassifying large inline content as a path
        return mb_strlen($path) < 2048 && @is_file($path) && @is_readable($path);
    }

    /**
     * Check if the given string is a raw ZIP archive.
     */
    private function isRawZip(string $data): bool
    {
        // ZIP magic header: "PK"
        return str_starts_with($data, 'PK');
    }

    /**
     * Check if the given string is a base64-encoded ZIP archive.
     */
    private function isBase64Zip(string $data): bool
    {
        if ($data === '' || preg_match('/[^A-Za-z0-9+\/=\r\n]/', $data)) {
            return false;
        }
        $decoded = base64_decode($data, true);

        return is_string($decoded) && $this->isRawZip($decoded);
    }
}
