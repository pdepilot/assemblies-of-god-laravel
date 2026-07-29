<?php

namespace App\Services\SundaySchool;

use RuntimeException;

final class ReportExportService
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $headers
     */
    public function toCsv(array $rows, array $headers): string
    {
        $out = fopen('php://temp', 'r+');
        if ($out === false) {
            throw new RuntimeException('Unable to create export.');
        }

        fputcsv($out, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($out, $line);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        if ($csv === false) {
            throw new RuntimeException('Unable to read export.');
        }

        return $csv;
    }
}
