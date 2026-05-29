<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportExportController extends Controller
{
    public function exportPdf(Request $request)
    {
        $startDate = Carbon::parse($request->get('startDate', now()->subDays(30)))->startOfDay();
        $endDate   = Carbon::parse($request->get('endDate', now()))->endOfDay();

        $data = $this->getReportData($startDate, $endDate);

        $pdf = Pdf::loadView('exports.report-pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'laporan-' . $startDate->format('d-m-Y') . '_' . $endDate->format('d-m-Y') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        $startDate = Carbon::parse($request->get('startDate', now()->subDays(30)))->startOfDay();
        $endDate   = Carbon::parse($request->get('endDate', now()))->endOfDay();

        $data = $this->getReportData($startDate, $endDate);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Penjualan');

        // --- Header judul ---
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'LAPORAN PENJUALAN NORTHERN CAFE');
        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Periode: ' . $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'));

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // --- Ringkasan stats ---
        $sheet->setCellValue('A4', 'Total Pendapatan');
        $sheet->setCellValue('B4', $data['stats']['revenue']);
        $sheet->setCellValue('A5', 'Total Pesanan');
        $sheet->setCellValue('B5', $data['stats']['orders']);
        $sheet->setCellValue('A6', 'Rata-rata per Order');
        $sheet->setCellValue('B6', $data['stats']['avg']);

        $sheet->getStyle('B4:B6')->getNumberFormat()->setFormatCode('"Rp "#,##0');
        $sheet->getStyle('A4:A6')->getFont()->setBold(true);

        // --- Tabel Produk Terlaris ---
        $sheet->setCellValue('A8', 'PRODUK TERLARIS');
        $sheet->getStyle('A8')->getFont()->setBold(true);

        $sheet->setCellValue('A9', 'No');
        $sheet->setCellValue('B9', 'Produk');
        $sheet->setCellValue('C9', 'Qty Terjual');
        $sheet->setCellValue('D9', 'Total Pendapatan');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '14B8A6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A9:D9')->applyFromArray($headerStyle);

        $row = 10;
        foreach ($data['topProducts'] as $idx => $item) {
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $item->product->name ?? '-');
            $sheet->setCellValue('C' . $row, $item->total_qty);
            $sheet->setCellValue('D' . $row, $item->total_revenue);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $row++;
        }

        // --- Tabel Detail Transaksi ---
        $row += 2;
        $sheet->setCellValue('A' . $row, 'DETAIL TRANSAKSI');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $txHeaders = ['A' => 'Waktu', 'B' => 'Kode Transaksi', 'C' => 'Kasir', 'D' => 'Metode', 'E' => 'Total'];
        foreach ($txHeaders as $col => $label) {
            $sheet->setCellValue($col . $row, $label);
        }
        $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($headerStyle);
        $row++;

        foreach ($data['transactions'] as $tx) {
            $sheet->setCellValue('A' . $row, $tx->created_at->format('d/m/Y H:i'));
            $sheet->setCellValue('B' . $row, $tx->transaction_code);
            $sheet->setCellValue('C' . $row, $tx->cashier->name ?? 'System');
            $sheet->setCellValue('D' . $row, $tx->payment_method);
            $sheet->setCellValue('E' . $row, $tx->total_amount);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $row++;
        }

        // Auto-size kolom
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'laporan-' . $startDate->format('d-m-Y') . '_' . $endDate->format('d-m-Y') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function getReportData(Carbon $startDate, Carbon $endDate): array
    {
        $query = Transaction::whereBetween('created_at', [$startDate, $endDate]);

        $totalRevenue = $query->sum('total_amount');
        $totalOrders  = $query->count();

        $topProducts = TransactionItem::with('product')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('product_id, SUM(qty) as total_qty, SUM(qty * price_at_sale) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(10)
            ->get();

        $transactions = Transaction::with('cashier')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();

        return [
            'stats' => [
                'revenue' => $totalRevenue,
                'orders'  => $totalOrders,
                'avg'     => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
            ],
            'topProducts'  => $topProducts,
            'transactions' => $transactions,
            'startDate'    => $startDate,
            'endDate'      => $endDate,
        ];
    }
}
