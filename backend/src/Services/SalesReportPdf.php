<?php

namespace App\Services;

use RuntimeException;

/**
 * Dependency-free landscape A4 PDF writer for the sales report.
 * Uses the built-in Helvetica fonts and one embedded JPEG logo.
 */
final class SalesReportPdf
{
    private const PAGE_W = 842;
    private const PAGE_H = 595;

    private const HEADERS = ['SN', 'Sale No.', 'Date', 'Customer', 'Cashier', 'Product', 'Qty', 'Unit Price', 'Line Total', 'Payment'];
    private const COL_X = [25, 50, 160, 225, 325, 405, 505, 550, 635, 730];
    private const COL_MAX_CHARS = [18, 18, 18, 17, 14, 17, 18, 18, 18, 14];

    /** @var list<string> */
    private array $pages = [];
    private string $content = '';

    public function __construct(private string $logoPath)
    {
        if (!is_file($logoPath) || !is_readable($logoPath)) {
            throw new RuntimeException('Report logo asset is missing.');
        }
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @param array{sale_count:int|string,total:float|string} $summary
     */
    public function render(array $rows, array $summary, string $from, string $to, string $downloadedBy): string
    {
        $header = function () use ($summary, $from, $to, $downloadedBy): void {
            $this->image(25, 16, 58, 58); // round emblem logo (square image)
            $this->centerText(37, 'MSINDA FOOD SHOP', 20, true);
            $this->centerText(58, 'SALES REPORT', 12, true);
            $this->text(25, 88, 'Report Period: ' . $from . ' to ' . $to, 9, true);
            $this->text(25, 104, 'Downloaded By: ' . $downloadedBy, 9);
            $this->text(25, 120, 'Generated On: ' . date('Y-m-d H:i'), 9);
            $this->text(25, 136, 'Total Sales: ' . number_format((float)$summary['sale_count'])
                . '    Total Amount: TZS ' . money($summary['total']), 9, true);
        };
        $tableHead = function (int $y): void {
            $this->rect(25, $y - 14, 782, 24);
            foreach (self::HEADERS as $i => $title) {
                $this->text(self::COL_X[$i] + 2, $y, $title, 8, true);
            }
        };

        $header();
        $y = 165;
        $tableHead($y);
        $y += 21;

        foreach ($rows as $index => $row) {
            if ($y > 555) {
                $this->newPage();
                $header();
                $this->text(25, 155, 'Sales Details (continued)', 10, true);
                $y = 177;
                $tableHead($y);
                $y += 21;
            }
            if ($index % 2 === 0) {
                $this->rect(25, $y - 11, 782, 18);
            }
            $values = [
                $index + 1,
                $row['sale_number'],
                $row['sale_date'],
                $row['customer'],
                $row['cashier'],
                $row['product'],
                number_format((float)$row['quantity'], 2),
                'TZS ' . money($row['unit_price']),
                'TZS ' . money($row['total']),
                $row['payment_method'],
            ];
            foreach ($values as $i => $value) {
                $text = (string)$value;
                $max = self::COL_MAX_CHARS[$i];
                if (strlen($text) > $max) {
                    $text = substr($text, 0, $max - 1) . '...';
                }
                $this->text(self::COL_X[$i] + 2, $y, $text, 7.2);
            }
            $this->line(25, $y + 6, 807, $y + 6);
            $y += 18;
        }

        if ($y > 545) {
            $this->newPage();
            $y = 45;
        }
        $this->text(25, $y + 14, 'REPORT TOTAL: TZS ' . money($summary['total']), 11, true);

        return $this->output();
    }

    /* ----------------------------------------------------------------- drawing */

    private function text(float $x, float $y, string $text, float $size = 8, bool $bold = false): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->content .= "0 0 0 rg BT /{$font} {$size} Tf 1 0 0 1 {$x} " . (self::PAGE_H - $y)
            . ' Tm (' . $this->escape($text) . ") Tj ET\n";
    }

    private function centerText(float $y, string $text, float $size, bool $bold): void
    {
        $width = strlen($text) * $size * ($bold ? 0.56 : 0.52);
        $this->text(max(25, (self::PAGE_W - $width) / 2), $y, $text, $size, $bold);
    }

    private function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->content .= "0.75 w 0.45 0.45 0.45 RG {$x1} " . (self::PAGE_H - $y1) . " m {$x2} " . (self::PAGE_H - $y2) . " l S\n";
    }

    private function rect(float $x, float $y, float $w, float $h): void
    {
        $this->content .= "0.91 0.96 0.93 rg {$x} " . (self::PAGE_H - $y - $h) . " {$w} {$h} re f\n";
    }

    private function image(float $x, float $y, float $w, float $h): void
    {
        $this->content .= "q {$w} 0 0 {$h} {$x} " . (self::PAGE_H - $y - $h) . " cm /Im1 Do Q\n";
    }

    private function newPage(): void
    {
        if ($this->content !== '') {
            $this->pages[] = $this->content;
        }
        $this->content = '';
    }

    private function escape(string $text): string
    {
        // Helvetica in a basic PDF is Latin-1; replace characters it cannot show.
        $text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    /* ------------------------------------------------------------------ output */

    private function output(): string
    {
        $this->newPage();

        $jpeg = (string)file_get_contents($this->logoPath);
        $size = getimagesize($this->logoPath) ?: [1, 1];

        $objects = [];
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[5] = "<< /Type /XObject /Subtype /Image /Width {$size[0]} /Height {$size[1]} /ColorSpace /DeviceRGB "
            . '/BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($jpeg) . " >>\nstream\n" . $jpeg . "\nendstream";

        $next = 6;
        $pageIds = [];
        foreach ($this->pages as $stream) {
            $contentId = $next++;
            $pageId = $next++;
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_W . ' ' . self::PAGE_H . '] '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << /Im1 5 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $pageIds[] = $pageId;
        }

        $kids = implode(' ', array_map(static fn(int $id) => $id . ' 0 R', $pageIds));
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $max = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= 'trailer << /Size ' . ($max + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }
}
