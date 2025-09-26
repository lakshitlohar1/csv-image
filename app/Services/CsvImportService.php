<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use League\Csv\Statement;

class CsvImportService
{
    private array $requiredColumns = ['sku', 'name', 'price'];
    private array $optionalColumns = ['description', 'stock_quantity', 'category', 'brand'];
    private array $importResults = [
        'total' => 0,
        'imported' => 0,
        'updated' => 0,
        'invalid' => 0,
        'duplicates' => 0,
        'errors' => []
    ];

    /**
     * Import products from CSV file
     */
    public function importProducts(string $filePath, int $userId): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = Statement::create()->process($csv);
            $this->importResults['total'] = count($records);
            
            DB::beginTransaction();
            
            foreach ($records as $index => $record) {
                $this->processProductRecord($record, $index + 1, $userId);
            }
            
            DB::commit();
            
            Log::info('CSV import completed', $this->importResults);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->importResults['errors'][] = 'Import failed: ' . $e->getMessage();
            Log::error('CSV import failed', ['error' => $e->getMessage()]);
        }
        
        return $this->importResults;
    }

    /**
     * Process a single product record
     */
    private function processProductRecord(array $record, int $lineNumber, int $userId): void
    {
        try {
            // Validate required columns
            if (!$this->hasRequiredColumns($record)) {
                $this->importResults['invalid']++;
                $this->importResults['errors'][] = "Line {$lineNumber}: Missing required columns";
                return;
            }

            // Validate data
            $validation = $this->validateProductData($record);
            if (!$validation['valid']) {
                $this->importResults['invalid']++;
                $this->importResults['errors'][] = "Line {$lineNumber}: {$validation['error']}";
                return;
            }

            // Check for duplicates
            $existingProduct = Product::where('sku', $record['sku'])->first();
            
            if ($existingProduct) {
                $this->updateProduct($existingProduct, $record, $userId);
                $this->importResults['updated']++;
            } else {
                $this->createProduct($record, $userId);
                $this->importResults['imported']++;
            }

        } catch (\Exception $e) {
            $this->importResults['invalid']++;
            $this->importResults['errors'][] = "Line {$lineNumber}: Processing error - " . $e->getMessage();
        }
    }

    /**
     * Check if record has required columns
     */
    private function hasRequiredColumns(array $record): bool
    {
        foreach ($this->requiredColumns as $column) {
            if (!isset($record[$column]) || empty(trim($record[$column]))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate product data
     */
    private function validateProductData(array $record): array
    {
        $validator = Validator::make($record, [
            'sku' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'error' => implode(', ', $validator->errors()->all())
            ];
        }

        return ['valid' => true];
    }

    /**
     * Create new product
     */
    private function createProduct(array $record, int $userId): Product
    {
        $productData = [
            'sku' => trim($record['sku']),
            'name' => trim($record['name']),
            'description' => trim($record['description'] ?? ''),
            'price' => (float) $record['price'],
            'stock_quantity' => (int) ($record['stock_quantity'] ?? 0),
            'category' => trim($record['category'] ?? ''),
            'brand' => trim($record['brand'] ?? ''),
            'attributes' => $this->extractAttributes($record),
            'is_active' => true,
        ];

        return Product::create($productData);
    }

    /**
     * Update existing product
     */
    private function updateProduct(Product $product, array $record, int $userId): Product
    {
        $updateData = [
            'name' => trim($record['name']),
            'description' => trim($record['description'] ?? $product->description),
            'price' => (float) $record['price'],
            'stock_quantity' => (int) ($record['stock_quantity'] ?? $product->stock_quantity),
            'category' => trim($record['category'] ?? $product->category),
            'brand' => trim($record['brand'] ?? $product->brand),
            'attributes' => $this->extractAttributes($record, $product->attributes),
        ];

        $product->update($updateData);
        return $product;
    }

    /**
     * Extract additional attributes from record
     */
    private function extractAttributes(array $record, array $existingAttributes = []): array
    {
        $attributes = $existingAttributes;
        
        // Extract any columns that are not in our defined columns
        $allColumns = array_merge($this->requiredColumns, $this->optionalColumns);
        
        foreach ($record as $key => $value) {
            $cleanKey = strtolower(trim($key));
            if (!in_array($cleanKey, $allColumns) && !empty(trim($value))) {
                $attributes[$cleanKey] = trim($value);
            }
        }
        
        return $attributes;
    }

    /**
     * Link image to product by SKU
     */
    public function linkImageToProduct(string $sku, int $imageId): bool
    {
        try {
            $product = Product::where('sku', $sku)->first();
            
            if (!$product) {
                return false;
            }

            $image = Image::find($imageId);
            
            if (!$image) {
                return false;
            }

            // Check if this image is already linked to this product
            if ($product->primary_image_id === $imageId) {
                return true; // No-op as per requirements
            }

            $product->update(['primary_image_id' => $imageId]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to link image to product', [
                'sku' => $sku,
                'image_id' => $imageId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get import results summary
     */
    public function getImportResults(): array
    {
        return $this->importResults;
    }

    /**
     * Reset import results
     */
    public function resetResults(): void
    {
        $this->importResults = [
            'total' => 0,
            'imported' => 0,
            'updated' => 0,
            'invalid' => 0,
            'duplicates' => 0,
            'errors' => []
        ];
    }

    /**
     * Validate CSV file structure
     */
    public function validateCsvStructure(string $filePath): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $header = $csv->getHeader();
            $missingColumns = [];
            
            foreach ($this->requiredColumns as $column) {
                if (!in_array($column, $header)) {
                    $missingColumns[] = $column;
                }
            }
            
            return [
                'valid' => empty($missingColumns),
                'missing_columns' => $missingColumns,
                'available_columns' => $header
            ];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
