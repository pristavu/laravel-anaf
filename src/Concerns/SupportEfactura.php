<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Concerns;

use Carbon\CarbonPeriod;
use Pristavu\Anaf\Enums\DocumentStandard;
use Pristavu\Anaf\Enums\MessageType;
use Pristavu\Anaf\Enums\XmlStandard;
use Pristavu\Anaf\Requests\Efactura\ConvertInvoiceRequest;
use Pristavu\Anaf\Requests\Efactura\DownloadInvoiceRequest;
use Pristavu\Anaf\Requests\Efactura\MessagesPaginatedRequest;
use Pristavu\Anaf\Requests\Efactura\MessagesRequest;
use Pristavu\Anaf\Requests\Efactura\MessageStatusRequest;
use Pristavu\Anaf\Requests\Efactura\UploadInvoiceRequest;
use Pristavu\Anaf\Requests\Efactura\ValidateInvoiceRequest;

trait SupportEfactura
{
    /**
     * Retrieve e-invoice messages for a specific CUI within the last given number of days.
     * https://mfinante.gov.ro/static/10/eFactura/listamesaje.html
     */
    public function messages(int $cif, ?int $days = 60, ?MessageType $type = null): array
    {
        $request = new MessagesRequest(cif: $cif, days: $days, type: $type);

        return $this->send($request)->dtoOrFail();
    }

    /**
     * Retrieve paginated e-invoice messages for a specific CUI within a given time range.
     * https://mfinante.gov.ro/static/10/eFactura/listamesaje.html
     */
    public function messagesPaginated(int $cif, CarbonPeriod $period, ?int $page = 1, ?MessageType $type = null): array
    {
        $request = new MessagesPaginatedRequest($cif, $period, $page, $type);

        return $this->send($request)->dtoOrFail();
    }

    /**
     * Download a specific e-invoice message by its ID.
     * https://mfinante.gov.ro/static/10/eFactura/descarcare.html
     */
    public function downloadInvoice(int $downloadId): array|string
    {
        $request = new DownloadInvoiceRequest(downloadId: $downloadId);

        return $this->send($request)->dtoOrFail();

    }

    /**
     * Validate an e-invoice XML file or content against the specified standard.
     * https://mfinante.gov.ro/static/10/eFactura/validare.html
     */
    public function validateInvoice(string $xml, ?DocumentStandard $standard = DocumentStandard::FACT1): array|object
    {
        $request = new ValidateInvoiceRequest(xml: $xml, standard: $standard);

        return $this->inLiveMode()->send($request)->dtoOrFail();
    }

    /**
     * Get the status of a specific e-invoice message by its ID.
     * https://mfinante.gov.ro/static/10/eFactura/staremesaj.html
     */
    public function messageStatus(int $uploadId): array|object
    {
        $request = new MessageStatusRequest($uploadId);

        return $this->send($request)->dtoOrFail();
    }

    /**
     * Upload an e-invoice XML file or content to the ANAF system.
     * https://mfinante.gov.ro/static/10/eFactura/upload.html
     */
    public function uploadInvoice(int $cif, string $xml, ?XmlStandard $standard = XmlStandard::UBL, ?bool $isExternal = false, ?bool $isSelfInvoice = false, ?bool $isLegalEnforcement = false): array|object|string
    {
        $request = new UploadInvoiceRequest(cif: $cif, xml: $xml, standard: $standard, isExternal: $isExternal, isSelfInvoice: $isSelfInvoice, isLegalEnforcement: $isLegalEnforcement);

        return $this->send($request)->dtoOrFail();

    }

    /**
     * Validate an e-invoice XML file or content against the specified standard.
     * https://mfinante.gov.ro/static/10/eFactura/xmltopdf.html
     */
    public function convertInvoice(string $xml, ?DocumentStandard $standard = DocumentStandard::FACT1, ?bool $withoutValidation = false): array|object
    {
        $request = new ConvertInvoiceRequest(xml: $xml, standard: $standard, withoutValidation: $withoutValidation);

        return $this->inLiveMode()->send($request)->dtoOrFail();

    }
}
