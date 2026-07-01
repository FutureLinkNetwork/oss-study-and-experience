<?php

namespace Tests\Unit;

use App\Http\Requests\StoreBusinessRequest;
use PHPUnit\Framework\TestCase;

class StoreBusinessRequestTest extends TestCase
{
    public function test_contact_and_document_fields_are_required(): void
    {
        $request = new StoreBusinessRequest;
        $rules = $request->rules();

        $requiredFields = [
            'contact_person',
            'contact_phone',
            'document_person',
            'document_address',
            'business_hours',
            'holiday',
        ];

        foreach ($requiredFields as $field) {
            $rule = $rules[$field] ?? '';
            $ruleString = is_array($rule) ? implode('|', $rule) : (string) $rule;

            $this->assertStringContainsString('required', $ruleString, "{$field} should be required.");
        }
    }

    public function test_bank_fields_are_required(): void
    {
        $request = new StoreBusinessRequest;
        $rules = $request->rules();

        $requiredFields = [
            'bank_code',
            'branch_code',
            'account_type',
            'account_number',
            'account_holder_name',
        ];

        foreach ($requiredFields as $field) {
            $rule = $rules[$field] ?? '';
            $ruleString = is_array($rule) ? implode('|', $rule) : (string) $rule;

            $this->assertStringContainsString('required', $ruleString, "{$field} should be required.");
        }
    }

    public function test_applicant_type_allows_government_agency(): void
    {
        $request = new StoreBusinessRequest;
        $rules = $request->rules();

        $rule = $rules['applicant_type'] ?? '';
        $ruleString = is_array($rule) ? implode('|', $rule) : (string) $rule;

        $this->assertStringContainsString('government_agency', $ruleString);
    }
}
