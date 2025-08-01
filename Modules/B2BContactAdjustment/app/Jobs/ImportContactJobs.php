<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\B2BContact\Models\B2BContacts;
use Modules\B2BContact\Models\B2BContactTypes;
use Modules\B2BContact\Models\ContactPersons;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\B2BContact\Models\ContactField;
use Modules\B2BContact\Models\ContactFieldValue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\NewContactData\App\Models\Import; 


class ImportContactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $import;
    protected $filePath;
    protected $contactTypeId;
    protected $contactTypeSlug; // mbokkyao., 'pharmacy-database', 'community'

    public $timeout = 3600; // 1 hour timeout for large imports

    public function __construct(Import $import, string $filePath, int $contactTypeId, string $contactTypeSlug)
    {
        $this->import = $import;
        $this->filePath = $filePath;
        $this->contactTypeId = $contactTypeId;
        $this->contactTypeSlug = $contactTypeSlug;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->import->update(['status' => 'processing', 'processed_rows' => 0, 'successful_rows' => 0, 'failed_rows' => 0, 'error_message' => null]);

        try {
            $reader = new XlsxReader();
            $spreadsheet = $reader->load($this->filePath);
            $worksheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true); // Get data as associative array

            // Assuming first row contains headers
            $headers = $this->normalizeKeys($worksheet[1]); // Normalize headers once

            $totalRows = count($worksheet) - 1; // Exclude header row
            $this->import->update(['total_rows' => $totalRows]);

            $successfulRows = 0;
            $failedRows = 0;
            $processedRows = 0;

            $chunkSize = 20; // As requested
            $rowsToProcess = array_slice($worksheet, 1); // Skip header row

            $parentCache = collect();
            if ($this->contactTypeSlug == 'pharmacy-database') {
                $parentNames = collect($rowsToProcess)->pluck('associated_pharmacy')->filter()->unique()->values()->all();
                $parentCache = Cache::remember('imported_parents_' . $this->import->id, now()->addMinutes(10), function() use ($parentNames) {
                    return B2BContacts::select('id', 'contact_name')->whereIn('contact_name', $parentNames)->get();
                });
            }

            foreach (array_chunk($rowsToProcess, $chunkSize) as $chunk) {
                DB::beginTransaction();
                try {
                    foreach ($chunk as $row) {
                        $processedRows++;
                        // Map row data to normalized headers
                        $data = [];
                        foreach ($headers as $normalizedKey => $originalHeader) {
                            $cellValue = $row[$originalHeader] ?? null; // Use original header from row array
                            $data[$normalizedKey] = $cellValue;
                        }

                        // Process boolean conversions
                        $data['cansativa_newsletter'] = filter_var($data['cansativa_newsletter'] ?? 'no', FILTER_VALIDATE_BOOLEAN);
                        $data['whatsapp_subscription'] = filter_var($data['whatsapp_subscription'] ?? 'no', FILTER_VALIDATE_BOOLEAN);
                        $data['email_subscription'] = filter_var($data['email_subscription'] ?? 'no', FILTER_VALIDATE_BOOLEAN);
                        $data['community_user'] = filter_var($data['community_user'] ?? 'no', FILTER_VALIDATE_BOOLEAN);

                        // Handle associated_pharmacy for pharmacy-database
                        if ($this->contactTypeSlug == 'pharmacy-database' && isset($data['associated_pharmacy'])) {
                            $parent = $parentCache->where('contact_name', $data['associated_pharmacy'])->first();
                            $data['contact_parent_id'] = $parent ? $parent->id : null;
                        } else {
                            $data['contact_parent_id'] = null; // Default to null if not pharmacy-database or no assoc_pharmacy
                        }
                        unset($data['associated_pharmacy']); // Remove from $data before saving main contact

                        $data['contact_type_id'] = $this->contactTypeId;

                        // Separate core contact fields from custom fields
                        $coreContactFields = [
                            'contact_name', 'contact_no', 'address', 'post_code', 'city', 'country', 'state', // Assuming state might be in some sheets
                            'contact_parent_id', 'contact_type_id', 'cansativa_newsletter', 'whatsapp_subscription',
                            'email_subscription', 'community_user', 'vat_id', 'amount_purchase', 'average_purchase',
                            'total_purchase', 'last_purchase_date', 'created_date', 'email', 'phone_no'
                        ];

                        $newContactData = [];
                        $customFieldsToStore = [];

                        foreach ($data as $key => $value) {
                            if (in_array($key, $coreContactFields)) {
                                if ($key === 'created_date' || $key === 'last_purchase_date') {
                                    // Try to parse dates that might be in Excel numeric format
                                    if (\PhpOffice\PhpSpreadsheet\Shared\Date::is=ExcelDate($value)) {
                                        $newContactData[$key] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
                                    } else {
                                        // Try to parse other date formats, or keep as is if already a string
                                        try {
                                            $newContactData[$key] = Carbon::parse($value)->format('Y-m-d H:i:s');
                                        } catch (\Exception $e) {
                                            $newContactData[$key] = null; // Or keep original value if parsing fails
                                        }
                                    }
                                } else {
                                    $newContactData[$key] = $value;
                                }
                            } else {
                                $customFieldsToStore[$key] = $value;
                            }
                        }

                        // Ensure required fields like 'contact_name' are not null if needed
                        if (empty($newContactData['contact_name'])) {
                            throw new \Exception('Contact name is required for import.');
                        }

                        $newContact = B2BContacts::create($newContactData);

                        // Handle ContactPersons if 'contact_person' is present and not null
                        if (isset($newContactData['contact_person']) && !empty($newContactData['contact_person'])) {
                            ContactPersons::create([
                                'contact_id' => $newContact->id,
                                'contact_name' => $newContactData['contact_person'],
                                'email' => $newContactData['email'] ?? null,
                                'phone_no' => $newContactData['phone_no'] ?? null
                            ]);
                        }

                        // Store custom fields
                        foreach ($customFieldsToStore as $fieldName => $fieldValue) {
                            if (!is_null($fieldValue) && $fieldValue !== '') { // Only store if value is not empty
                                $contactField = ContactField::firstOrCreate(['field_name' => $fieldName]);
                                ContactFieldValue::create([
                                    'contact_id' => $newContact->id,
                                    'contact_field_id' => $contactField->id,
                                    'value' => (string) $fieldValue, // Ensure value is stored as string/text
                                ]);
                            }
                        }
                        $successfulRows++;
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failedRows += count($chunk); // Mark entire chunk as failed if one fails
                    Log::error("ImportContactsJob chunk error for import ID {$this->import->id}: " . $e->getMessage());
                    // We don't rethrow here, just log and continue to process next chunks
                } finally {
                    $this->import->increment('processed_rows', count($chunk));
                    $this->import->update([
                        'successful_rows' => DB::raw('successful_rows + ' . $successfulRows),
                        'failed_rows' => DB::raw('failed_rows + ' . $failedRows),
                    ]);
                    // Reset counts for next chunk
                    $successfulRows = 0;
                    $failedRows = 0;
                }
            }

            $this->import->update(['status' => 'completed']);
        } catch (\Exception $e) {
            $this->import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            \Log::error("ImportContactsJob failed for import ID {$this->import->id}: " . $e->getMessage());
        } finally {
            // Clean up the temporary file after processing
            if (Storage::disk('local')->exists($this->filePath)) {
                Storage::disk('local')->delete($this->filePath);
            }
        }
    }

    /**
     * Normalizes header keys (e.g., "Pharmacy Name" -> "pharmacy_name").
     * This version adapts for PhpSpreadsheet's toArray(null, true, true, true) which uses original column letters for keys
     *
     * @param array $headerRow From PhpSpreadsheet toArray(null, true, true, true)
     * @return array Normalized headers mapping original header text to normalized key.
     */
    private function normalizeKeys(array $headerRow): array
    {
        $normalized = [];
        foreach ($headerRow as $columnLetter => $value) { // $columnLetter will be 'A', 'B', etc.
            if (!empty($value)) {
                $newKey = strtolower(str_replace(' ', '_', $value));
                $normalized[$newKey] = $columnLetter; // Map normalized key to original column letter
            }
        }
        return $normalized;
    }
}