<?php

namespace App\Support;

use App\Models\Hotel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exporta un reporte (estructura de App\Support\Reportes) a Excel (.xlsx).
 * Montos, enteros y fechas quedan como números/fechas reales: se pueden sumar y filtrar en Excel.
 */
class ReporteExcel
{
    private const FORMATOS = [
        'moneda'     => '"Bs" #,##0.00',
        'entero'     => '#,##0',
        'porcentaje' => '0.0%',
        'fecha'      => 'dd/mm/yyyy',
        'fechahora'  => 'dd/mm/yyyy hh:mm',
    ];

    public static function descargar(array $reporte, string $archivo): StreamedResponse
    {
        $libro = new Spreadsheet();
        $hoja  = $libro->getActiveSheet();
        $hoja->setTitle(mb_substr($reporte['titulo'], 0, 31));
        $libro->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

        $fila = 1;
        $hoja->setCellValue("A{$fila}", Hotel::datos()['nombre']);
        $hoja->getStyle("A{$fila}")->getFont()->setBold(true)->getColor()->setRGB('1D3A2E');
        $fila++;
        $hoja->setCellValue("A{$fila}", $reporte['titulo']);
        $hoja->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(14);
        $fila++;
        $hoja->setCellValue("A{$fila}", $reporte['periodo']);
        $fila++;
        $hoja->setCellValue("A{$fila}", 'Generado el ' . now()->format('d/m/Y H:i'));
        $hoja->getStyle("A{$fila}")->getFont()->getColor()->setRGB('5D6A63');
        $fila += 2;

        foreach ($reporte['cifras'] as $cifra) {
            $hoja->setCellValue("A{$fila}", $cifra['texto']);
            $hoja->setCellValueExplicit("B{$fila}", $cifra['valor'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $hoja->getStyle("B{$fila}")->getFont()->setBold(true);
            $hoja->getStyle("B{$fila}")->getAlignment()->setHorizontal('right');
            $fila++;
        }
        $fila++;

        foreach ($reporte['tablas'] as $tabla) {
            $fila = self::tabla($hoja, $tabla, $fila) + 1;
        }

        if (! empty($reporte['nota'])) {
            $hoja->setCellValue("A{$fila}", $reporte['nota']);
            $hoja->getStyle("A{$fila}")->getFont()->setItalic(true)->getColor()->setRGB('5D6A63');
        }

        // Encabezado y nota en celdas combinadas: así no ensanchan la columna A
        $columnas = max(4, ...array_map(fn ($t) => count($t['columnas']), $reporte['tablas']));
        $ultima = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnas);
        foreach ([1, 2, 3, 4] as $f) {
            $hoja->mergeCells("A{$f}:{$ultima}{$f}");
        }
        if (! empty($reporte['nota'])) {
            $hoja->mergeCells("A{$fila}:{$ultima}{$fila}");
        }
        for ($c = 1; $c <= $columnas; $c++) {
            $hoja->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        return new StreamedResponse(function () use ($libro) {
            (new Xlsx($libro))->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $archivo . '.xlsx"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // Escribe una tabla desde $fila y devuelve la fila siguiente libre
    private static function tabla(Worksheet $hoja, array $tabla, int $fila): int
    {
        $n = count($tabla['columnas']);
        $ultima = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($n);

        $hoja->setCellValue("A{$fila}", $tabla['titulo']);
        $hoja->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(12);
        $fila++;

        foreach ($tabla['columnas'] as $i => $col) {
            $hoja->setCellValue([$i + 1, $fila], $col['texto']);
            if (Reportes::esNumero($col['tipo'])) {
                $hoja->getStyle([$i + 1, $fila])->getAlignment()->setHorizontal('right');
            }
        }
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D3A2E']],
        ]);
        $fila++;

        if (empty($tabla['filas'])) {
            $hoja->setCellValue("A{$fila}", $tabla['vacio']);
            $hoja->getStyle("A{$fila}")->getFont()->getColor()->setRGB('5D6A63');

            return $fila + 2;
        }

        foreach ($tabla['filas'] as $valores) {
            self::fila($hoja, $tabla['columnas'], $valores, $fila);
            $fila++;
        }

        if (! empty($tabla['pie'])) {
            self::fila($hoja, $tabla['columnas'], $tabla['pie'], $fila, true);
            $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->applyFromArray([
                'font'    => ['bold' => true],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1D3A2E']]],
            ]);
            $fila++;
        }

        return $fila + 1;
    }

    // En el pie, la primera celda es el texto "Total" (aunque la columna sea de fechas)
    private static function fila(Worksheet $hoja, array $columnas, array $valores, int $fila, bool $pie = false): void
    {
        foreach ($columnas as $i => $col) {
            $valor = $valores[$i] ?? null;
            $celda = [$i + 1, $fila];

            if ($valor === null || $valor === '') {
                continue;
            }

            switch ($pie && $i === 0 ? 'texto' : $col['tipo']) {
                case 'moneda':
                case 'entero':
                    $hoja->setCellValue($celda, (float) $valor);
                    break;
                case 'porcentaje':
                    $hoja->setCellValue($celda, (float) $valor / 100);
                    break;
                case 'fecha':
                case 'fechahora':
                    $hoja->setCellValue($celda, Date::PHPToExcel(Carbon::parse($valor)));
                    break;
                default:
                    $hoja->setCellValueExplicit($celda, (string) $valor, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }

            if (isset(self::FORMATOS[$col['tipo']]) && ! ($pie && $i === 0)) {
                $hoja->getStyle($celda)->getNumberFormat()->setFormatCode(self::FORMATOS[$col['tipo']]);
            }
        }
    }
}
