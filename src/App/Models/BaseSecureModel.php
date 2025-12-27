<?php

namespace PROLANCEE\Support\App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use PROLANCEE\Support\Classes\Crypto\Encrypter;

abstract class BaseSecureModel extends Model
{
    /**
     * Sensitive fields
     */
    protected array $autoHidden = [
        'password',
        'remember_token',
        'api_token',
        'access_token',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($model) => $model->validateSecurity());
        static::updating(fn ($model) => $model->validateSecurity());
        static::retrieved(fn ($model) => $model->applyAutoHidden());
    }

    /* =========================================================
     | SECURITY VALIDATION
     ========================================================= */
    protected function validateSecurity(): void
    {
        if (! is_string($this->secureTable()) || $this->secureTable() === '') {
            throw new LogicException(
                'Model table must be defined: ' . static::class
            );
        }

        if (! is_array($this->secureFillable()) || empty($this->secureFillable())) {
            throw new LogicException(
                'Fillable must be defined: ' . static::class
            );
        }
    }

    /* =========================================================
     | CUSTOM GETTERS (PACKAGE SAFE)
     ========================================================= */

    /**
     * Secure table getter
     */
    public function secureTable(): string
    {
        return (string) ($this->table ?? '');
    }

    /**
     * Secure fillable getter
     */
    public function secureFillable(): array
    {
        return is_array($this->fillable ?? null)
            ? array_values(array_unique($this->fillable))
            : [];
    }

    /**
     * Secure hidden getter (auto + model)
     */
    public function secureHidden(): array
    {
        $modelHidden = is_array($this->hidden ?? null)
            ? $this->hidden
            : [];

        return array_values(array_unique(array_merge(
            $this->autoHidden,
            $modelHidden
        )));
    }

    /**
     * Apply hidden fields to model
     */
    protected function applyAutoHidden(): void
    {
        $this->hidden = $this->secureHidden();
    }

    /**
     * Mandatory rules
     */
    public function secureMandatory(): array
    {
        return property_exists($this, 'mandatory') && is_array($this->mandatory)
            ? $this->mandatory
            : [];
    }

    /**
     * Filter input data strictly by fillable fields.
     */
    public function filterRow(array $data): array
    {
        return array_intersect_key(
            $data,
            array_flip($this->secureFillable())
        );
    }

    /**
     * Validates mandatory fields for a row based on endpoint-specific rules.
     *
     * @param array  $row          Row data (already filtered).
     * @param object $model        Model instance.
     * @param string $requestPath  Current request URI path.
     * @param string $tableName    Table name.
     * @param array  $encryption   Encryption mapping.
     * @param string $startPoint   Start point base url.
     * @return array               List of missing mandatory fields with messages.
     */
    public function checkMandatoryRow(
        array $row, 
        $model, 
        string $requestPath, 
        string $tableName, 
        $encryption,
        string $startPoint
    ): array {
        $missing = [];

        $mandatoryRules = $this->secureMandatory();

        foreach ($mandatoryRules as $endpoint => $rules) {

            $endpointPath = $startPoint . trim($endpoint, '/');

            if ($requestPath !== $endpointPath) {
                continue;
            }

            foreach ($rules['required'] ?? [] as $field => $message) {

                if (
                    ! array_key_exists($field, $row) ||
                    $row[$field] === null ||
                    $row[$field] === ''
                ) {
                    $encKey = Encrypter::getPayloadEncryptDataColumn(
                        $encryption,
                        $tableName,
                        $field
                    );

                    $missing[] = [$encKey => $message];
                }
            }
        }

        return $missing;
    }
}
