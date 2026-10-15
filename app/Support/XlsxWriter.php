<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Writes a plain .xlsx workbook with one sheet, with no extra package: a bold header row, frozen under the top
 * row, and cells that are either text or numbers. Text cells keep leading zeros, so a phone number such as
 * 01805962220 stays as it is in Excel.
 */
final class XlsxWriter
{
    /** Cell values with this wrapper are shown as whole taka amounts, e.g. 12,500. */
    public const MONEY = 'money';

    public static function isAvailable(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, string|int|float|null>>  $rows  money amounts are passed as ['money', 1250.5]
     * @param  array<int, int>  $widths  column widths in characters
     * @return string path of a temporary .xlsx file; the caller deletes it
     */
    public static function create(string $sheetName, array $headers, iterable $rows, array $widths = []): string
    {
        if (! self::isAvailable()) {
            throw new RuntimeException('The PHP zip extension is needed to write .xlsx files.');
        }

        $sheetPath = tempnam(sys_get_temp_dir(), 'xlsx-sheet');
        $archivePath = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        $sheet = fopen($sheetPath, 'w');

        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>');

        if ($widths !== []) {
            fwrite($sheet, '<cols>');
            foreach (array_values($widths) as $index => $width) {
                fwrite($sheet, '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>');
            }
            fwrite($sheet, '</cols>');
        }

        fwrite($sheet, '<sheetData>');
        fwrite($sheet, self::row(1, $headers, header: true));

        $rowNumber = 1;
        foreach ($rows as $row) {
            fwrite($sheet, self::row(++$rowNumber, $row));
        }

        fwrite($sheet, '</sheetData></worksheet>');
        fclose($sheet);

        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the .xlsx file.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRelationships());
        $zip->addFromString('xl/workbook.xml', self::workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelationships());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
        $zip->close();

        unlink($sheetPath);

        return $archivePath;
    }

    /**
     * @param  array<int, string|int|float|array{0: string, 1: int|float}|null>  $cells
     */
    private static function row(int $number, array $cells, bool $header = false): string
    {
        $xml = '<row r="'.$number.'">';

        foreach (array_values($cells) as $index => $value) {
            $reference = self::columnName($index).$number;

            if ($header) {
                $xml .= '<c r="'.$reference.'" s="1" t="inlineStr"><is><t>'.self::escape((string) $value).'</t></is></c>';
            } elseif (is_array($value) && ($value[0] ?? null) === self::MONEY) {
                $xml .= '<c r="'.$reference.'" s="2"><v>'.(float) $value[1].'</v></c>';
            } elseif (is_int($value) || is_float($value)) {
                $xml .= '<c r="'.$reference.'"><v>'.$value.'</v></c>';
            } elseif ($value !== null && $value !== '') {
                $xml .= '<c r="'.$reference.'" t="inlineStr"><is><t xml:space="preserve">'.self::escape((string) $value).'</t></is></c>';
            }
        }

        return $xml.'</row>';
    }

    /** A, B, … Z, AA, AB … */
    private static function columnName(int $index): string
    {
        $name = '';

        for ($number = $index + 1; $number > 0; $number = intdiv($number - 1, 26)) {
            $name = chr(65 + ($number - 1) % 26).$name;
        }

        return $name;
    }

    /** Text safe for XML: special characters escaped and characters XML forbids removed. */
    private static function escape(string $text): string
    {
        $clean = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text) ?? '';

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(string $sheetName): string
    {
        // Excel limits sheet names to 31 characters and forbids : \ / ? * [ ]
        $name = mb_substr(preg_replace('/[:\\\\\/?*\[\]]/u', ' ', $sheetName) ?? 'Sheet1', 0, 31);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.self::escape($name).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    /** Style 0 plain, 1 bold on grey for the header, 2 whole-number money with thousands separators. */
    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
