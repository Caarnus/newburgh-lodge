<?php

namespace App\Services\People\Imports;

use Carbon\Carbon;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class MemberRosterSpreadsheetService
{
    public function parse(string $absolutePath): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $worksheet = $spreadsheet->getSheet(0);

        $highestRow = $worksheet->getHighestDataRow();
        $highestColumn = $worksheet->getHighestDataColumn();
        $headerRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, false)[0];

        $headers = $this->normalizeHeaders($headerRow);
        $rows = [];

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $values = $worksheet->rangeToArray('A' . $rowNumber . ':' . $highestColumn . $rowNumber, null, true, true, false)[0];
            $raw = $this->combineRow($headers, $values);

            if ($this->isBlankRow($raw)) {
                continue;
            }

            $normalized = $this->normalizeRosterRow($raw);

            $rows[] = [
                'row_number' => $rowNumber,
                'raw' => $raw,
                'normalized' => $normalized,
            ];
        }

        return $rows;
    }

    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $header = trim((string) $header);

            return $header === '' ? null : $header;
        }, $headers);
    }

    protected function combineRow(array $headers, array $values): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            if ($header === null) {
                continue;
            }

            $row[$header] = $values[$index] ?? null;
        }

        return $row;
    }

    protected function isBlankRow(array $raw): bool
    {
        foreach ($raw as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalizeRosterRow(array $raw): array
    {
        $status = $this->normalizeString($raw['Status'] ?? null);

        return [
            'member_number' => $this->normalizeString($raw['Member ID'] ?? null),
            'status' => $status,
            'member_type' => $this->mapMemberType($status),
            'first_name' => $this->normalizeString($raw['First'] ?? null),
            'middle_name' => $this->normalizeString($raw['Middle'] ?? null),
            'last_name' => $this->normalizeString($raw['Last'] ?? null),
            'suffix' => $this->normalizeString($raw['Suffix'] ?? null),
            'address_line_1' => $this->normalizeString($raw['Address'] ?? null),
            'city' => $this->normalizeString($raw['City'] ?? null),
            'state' => $this->normalizeString($raw['State'] ?? null),
            'postal_code' => $this->normalizeString($raw['ZIP'] ?? null),
            'birth_date' => $this->normalizeDate($raw['Birthday'] ?? null),
            'ea_date' => $this->normalizeDate($raw['EA'] ?? null),
            'fc_date' => $this->normalizeDate($raw['FC'] ?? null),
            'mm_date' => $this->normalizeDate($raw['MM'] ?? null),
            'phone' => $this->normalizePhone($raw['Phone'] ?? null),
            'email' => $this->normalizeEmail($raw['Email'] ?? null),
            'spouse_name' => $this->normalizeString($raw['Spouse'] ?? null),
            'full_name_source' => $this->normalizeString($raw['Full Name'] ?? null),
        ];
    }

    protected function mapMemberType(?string $status): ?string
    {
        return match ($status) {
            'Master Mason', 'Entered Apprentice', 'Fellow Craft' => 'member',
            default => null,
        };
    }

    protected function normalizeString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function normalizeEmail(mixed $value): ?string
    {
        $value = $this->normalizeString($value);

        return $value ? mb_strtolower($value) : null;
    }

    protected function normalizePhone(mixed $value): ?string
    {
        $value = $this->normalizeString($value);

        if (! $value) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) !== 10) {
            return $value;
        }

        return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
