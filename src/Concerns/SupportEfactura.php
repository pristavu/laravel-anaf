<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Concerns;

use Carbon\CarbonPeriod;
use Einvoicing\Invoice;
use InvalidArgumentException;
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
use Pristavu\Anaf\Responses\Efactura\ConvertInvoiceErrorResponse;
use Pristavu\Anaf\Responses\Efactura\ConvertInvoiceResponse;
use Pristavu\Anaf\Responses\Efactura\DownloadInvoiceResponse;
use Pristavu\Anaf\Responses\Efactura\ErrorResponse;
use Pristavu\Anaf\Responses\Efactura\MessagesPaginatedResponse;
use Pristavu\Anaf\Responses\Efactura\MessagesResponse;
use Pristavu\Anaf\Responses\Efactura\MessageStatusErrorResponse;
use Pristavu\Anaf\Responses\Efactura\MessageStatusResponse;
use Pristavu\Anaf\Responses\Efactura\UploadInvoiceErrorResponse;
use Pristavu\Anaf\Responses\Efactura\UploadInvoiceResponse;
use Pristavu\Anaf\Responses\Efactura\ValidateErrorResponse;
use Pristavu\Anaf\Responses\Efactura\ValidateInvoiceResponse;
use Pristavu\Anaf\Support\Validate;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

/**
 * Trait providing methods to interact with the ANAF e-invoicing (e-Factura) system.
 */
trait SupportEfactura
{
    /**
     * Retrieve e-invoice messages for a specific Fiscal Identification Code within the last given number of days.
     *
     * @param  int  $cif  The Fiscal Identification Code of the entity.
     * @param  int|null  $days  The number of days to look back for messages. Defaults to 60 days if null.
     * @param  MessageType|null  $type  Optional filter for message type.
     *
     * @throws FatalRequestException
     * @throws RequestException
     * @throws InvalidArgumentException
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/listamesaje.html
     */
    public function messages(int $cif, ?int $days = 60, ?MessageType $type = null): MessagesResponse|ErrorResponse
    {
        if (! Validate::cif($cif)) {
            throw new InvalidArgumentException('The provided CIF is invalid.');
        }

        $request = new MessagesRequest(cif: $cif, days: $days, type: $type);

        return $this->send($request)->dtoOrFail();
    }

    /**
     * Retrieve paginated e-invoice messages for a specific Fiscal Identification Code within a given time range.
     *
     * @param  int  $cif  The Fiscal Identification Code of the entity.
     * @param  CarbonPeriod  $period  The time period to filter messages.
     * @param  int|null  $page  The page number for pagination. Defaults to 1 if null.
     * @param  MessageType|null  $type  Optional filter for message type. Defaults to null if not provided.
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/listamesaje.html
     */
    public function messagesPaginated(int $cif, CarbonPeriod $period, ?int $page = 1, ?MessageType $type = null): MessagesPaginatedResponse|ErrorResponse
    {
        if (! Validate::cif($cif)) {
            throw new InvalidArgumentException('The provided CIF is invalid.');
        }

        $request = new MessagesPaginatedRequest($cif, $period, $page, $type);

        return $this->send($request)->dtoOrFail();
    }

    /**
     * Download a specific e-invoice message by its ID.
     *
     * @param  int  $downloadId  The ID of the e-invoice message to download.
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/descarcare.html
     */
    public function downloadInvoice(int $downloadId): DownloadInvoiceResponse|ErrorResponse
    {
        $request = new DownloadInvoiceRequest(downloadId: $downloadId);

        if ($this->invalidateCache) {
            $request->invalidateCache();
        }

        if ($this->disableCaching) {
            $request->disableCaching();
        }

        // Reset cache flags after applying them for this request
        $this->invalidateCache = false;
        $this->disableCaching = false;

        return $this->send($request)->dtoOrFail();

    }

    /**
     * Validate an e-invoice XML file or content against the specified standard.
     *
     * @param  string  $xml  The XML content or path of the e-invoice to validate.
     * @param  DocumentStandard|null  $standard  The document standard to validate against.
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/validare.html
     */
    public function validateInvoice(string $xml, ?DocumentStandard $standard = DocumentStandard::FACT1): ValidateInvoiceResponse|ValidateErrorResponse
    {
        $request = new ValidateInvoiceRequest(xml: $xml, standard: $standard);

        return $this->inLiveMode()->send($request)->dtoOrFail();
    }

    /**
     * Upload an e-invoice XML file or content to the ANAF system.
     *
     * @param  int  $cif  The Fiscal Identification Code of the entity.
     * @param  string  $xml  The XML content or path of the e-invoice to upload.
     * @param  XmlStandard|null  $standard  The XML standard of the e-invoice. Defaults to UBL.
     * @param  bool|null  $isExternal  Indicates if the invoice customer is external (not a romanian entity). Defaults to false.
     * @param  bool|null  $isSelfInvoice  Indicates if the invoice is a self-invoice. Defaults to false.
     * @param  bool|null  $isLegalEnforcement  Indicates if the invoice is related to legal enforcement. Defaults to false.
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/upload.html
     */
    public function uploadInvoice(int $cif, string $xml, ?XmlStandard $standard = XmlStandard::UBL, ?bool $isExternal = false, ?bool $isSelfInvoice = false, ?bool $isLegalEnforcement = false): UploadInvoiceResponse|UploadInvoiceErrorResponse
    {
        if (! Validate::cif($cif)) {
            throw new InvalidArgumentException('The provided CIF is invalid.');
        }

        $request = new UploadInvoiceRequest(cif: $cif, xml: $xml, standard: $standard, isExternal: $isExternal, isSelfInvoice: $isSelfInvoice, isLegalEnforcement: $isLegalEnforcement);

        return $this->send($request)->dtoOrFail();

    }

    /**
     * Upload an e-invoice XML file or content to the ANAF system.
     *
     * @param  int  $cif  The Fiscal Identification Code of the entity.
     * @param  string  $xml  The XML content or path of the e-invoice to upload.
     * @param  XmlStandard|null  $standard  The XML standard of the e-invoice. Defaults to UBL.
     * @param  bool|null  $isExternal  Indicates if the invoice customer is external (not a romanian entity). Defaults to false.
     * @param  bool|null  $isSelfInvoice  Indicates if the invoice is a self-invoice. Defaults to false.
     * @param  bool|null  $isLegalEnforcement  Indicates if the invoice is related to legal enforcement. Defaults to false.
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/upload.html
     */
    public function uploadInvoiceB2C(int $cif, string $xml, ?XmlStandard $standard = XmlStandard::UBL, ?bool $isExternal = false, ?bool $isSelfInvoice = false, ?bool $isLegalEnforcement = false): UploadInvoiceResponse|UploadInvoiceErrorResponse
    {
        if (! Validate::cif($cif)) {
            throw new InvalidArgumentException('The provided CIF is invalid.');
        }

        $request = new UploadInvoiceRequest(cif: $cif, xml: $xml, standard: $standard, isExternal: $isExternal, isSelfInvoice: $isSelfInvoice, isLegalEnforcement: $isLegalEnforcement);
        $request->setB2C(true);

        return $this->send($request)->dtoOrFail();

    }

    /**
     * Get the status of a specific e-invoice message by its ID.
     *
     * @param  int  $uploadId  The ID of the e-invoice message to check the status for.
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/staremesaj.html
     */
    public function messageStatus(int $uploadId): MessageStatusResponse|MessageStatusErrorResponse
    {
        $request = new MessageStatusRequest($uploadId);

        return $this->send($request)->dtoOrFail();
    }

    /**
     * Convert an e-invoice XML file or content to PDF.
     *
     * @param  string  $xml  The XML content or path of the e-invoice to convert.
     * @param  DocumentStandard|null  $standard  The document standard to convert against.
     * @param  bool|null  $withoutValidation  If true, skips validation before conversion
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @see https://mfinante.gov.ro/static/10/eFactura/xmltopdf.html
     */
    public function convertInvoice(string $xml, ?DocumentStandard $standard = DocumentStandard::FACT1, ?bool $withoutValidation = false): ConvertInvoiceResponse|ConvertInvoiceErrorResponse
    {
        $request = new ConvertInvoiceRequest(xml: $xml, standard: $standard, withoutValidation: $withoutValidation);

        return $this->inLiveMode()->send($request)->dtoOrFail();

    }
}
