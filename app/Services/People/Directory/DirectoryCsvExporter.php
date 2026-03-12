<?php

namespace App\Services\People\Directory;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DirectoryCsvExporter
{
    public function download(string $prefix, array $headers, Collection $rows, callable $mapRow): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $mapRow) {
            $output = fopen('php://output', 'w');

            fputcsv($output, $headers);

            foreach ($rows as $row) {
                fputcsv($output, $mapRow($row));
            }

            fclose($output);
        }, $prefix.'-'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
