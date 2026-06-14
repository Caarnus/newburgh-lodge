<?php

namespace App\Services\People\Directory;

use App\Models\Person;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class DirectoryAddressLabelExporter
{
    public function download(string $prefix, Collection $people): Response
    {
        [$labelPeople, $missingAddressPeople] = $people
            ->partition(fn (Person $person) => $this->hasUsableAddress($person));

        $labels = $labelPeople
            ->map(fn (Person $person) => $this->labelFor($person))
            ->values()
            ->all();

        $missingAddresses = $missingAddressPeople
            ->map(fn (Person $person) => $this->missingAddressFor($person))
            ->values()
            ->all();

        $filename = sprintf('%s-%s.html', $prefix, now()->format('Y-m-d-His'));

        return response()
            ->view('exports.people.address-labels', [
                'labels' => $labels,
                'missingAddresses' => $missingAddresses,
            ])
            ->withHeaders([
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
    }

    protected function hasUsableAddress(Person $person): bool
    {
        return trim((string) $person->address_line_1) !== ''
            && trim((string) $person->city) !== ''
            && trim((string) $person->state) !== ''
            && trim((string) $person->postal_code) !== '';
    }

    protected function labelFor(Person $person): array
    {
        $cityState = collect([$person->city, $person->state])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->implode(', ');

        $cityStatePostal = collect([$cityState, $person->postal_code])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->implode(' ');

        return [
            'lines' => collect([
                $person->display_name,
                $person->address_line_1,
                $person->address_line_2,
                $cityStatePostal,
            ])
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    protected function missingAddressFor(Person $person): array
    {
        return [
            'name' => $person->display_name,
            'details' => collect([
                $person->address_line_1,
                $person->address_line_2,
                $person->city,
                $person->state,
                $person->postal_code,
            ])
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->implode(', '),
        ];
    }
}
