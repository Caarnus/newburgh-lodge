<?php

namespace App\Services\People\Directory;

use Illuminate\Http\Request;

class DirectoryFilterBuilder
{
    public function fromRequest(Request $request, string $defaultSort = 'name', bool $includePagination = true): array
    {
        $filters = [
            'q' => $this->stringOrNull($request->input('q')),
            'status' => $this->stringOrNull($request->input('status')),
            'relationship_type' => $this->stringOrNull($request->input('relationship_type')),
            'has_email' => $this->stringOrNull($request->input('has_email')),
            'has_phone' => $this->stringOrNull($request->input('has_phone')),
            'last_contact_older_than_days' => $this->integerOrNull($request->input('last_contact_older_than_days')),
            'hide_deceased' => $request->boolean('hide_deceased'),
            'sort' => $this->stringOrNull($request->input('sort')) ?: $defaultSort,
        ];

        if ($includePagination) {
            $filters['page'] = max(1, (int) $request->integer('page', 1));
            $filters['per_page'] = max(10, min(100, (int) $request->integer('per_page', 25)));
        }

        return $filters;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
