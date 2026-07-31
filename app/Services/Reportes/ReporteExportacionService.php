<?php

namespace App\Services\Reportes;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TCPDF;

class ReporteExportacionService
{
    public function xlsx(array $reporte): string
    {
        $book = new Spreadsheet();
        $book->getProperties()->setCreator('La Suriana')->setTitle($reporte['titulo'])->setSubject('Reporte operativo de '.$reporte['sucursal']);
        $resumen = $book->getActiveSheet(); $resumen->setTitle('Resumen'); $resumen->setShowGridlines(false);
        $resumen->mergeCells('A1:D2')->setCellValue('A1', $reporte['titulo']);
        $resumen->getStyle('A1:D2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F6CBD');
        $resumen->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setRGB('FFFFFF'); $resumen->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $resumen->fromArray([['Sucursal',$reporte['sucursal']],['Periodo',$reporte['desde'].' al '.$reporte['hasta']],['Generado por',$reporte['generado_por']],['Fecha de generación',now()->format('d/m/Y H:i')]], null, 'A4');
        $fila=10; $resumen->setCellValue("A{$fila}",'Indicadores principales'); $resumen->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(12); $fila++;
        foreach ($reporte['kpis'] as $nombre=>$valor) { $resumen->setCellValue("A{$fila}",$nombre)->setCellValue("B{$fila}",$valor); $resumen->getStyle("A{$fila}:B{$fila}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('DDE4EA'); $fila++; }
        $resumen->getColumnDimension('A')->setWidth(28); $resumen->getColumnDimension('B')->setWidth(34);

        $detalle=$book->createSheet()->setTitle('Detalle'); $detalle->setShowGridlines(false); $headers=$reporte['encabezados']; $last=Coordinate::stringFromColumnIndex(max(1,count($headers)));
        $detalle->mergeCells("A1:{$last}1")->setCellValue('A1',$reporte['titulo']); $detalle->mergeCells("A2:{$last}2")->setCellValue('A2',$reporte['sucursal'].' · '.$reporte['desde'].' al '.$reporte['hasta']);
        $detalle->getStyle("A1:{$last}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F6CBD'); $detalle->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
        $detalle->fromArray($headers,null,'A4'); $detalle->fromArray(collect($reporte['rows'])->map(fn($r)=>array_values((array)$r))->all(),null,'A5');
        $detalle->getStyle("A4:{$last}4")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF'); $detalle->getStyle("A4:{$last}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
        $detalle->setAutoFilter("A4:{$last}".max(4,$detalle->getHighestRow())); $detalle->freezePane('A5');
        foreach($headers as $i=>$header){$column=Coordinate::stringFromColumnIndex($i+1);$detalle->getColumnDimension($column)->setAutoSize(true);if(preg_match('/venta|total|importe|descuento|promedio|monto|esperado|reportado|diferencia|costo|valor|subtotal|iva|gastos|retiros/i',$header))$detalle->getStyle("{$column}5:{$column}".max(5,$detalle->getHighestRow()))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);}
        $detalle->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0); $detalle->getPageMargins()->setTop(.4)->setBottom(.4)->setLeft(.3)->setRight(.3);
        $book->setActiveSheetIndex(0); ob_start(); (new Xlsx($book))->save('php://output'); $contenido=(string)ob_get_clean(); $book->disconnectWorksheets(); return $contenido;
    }

    public function pdf(array $reporte): string
    {
        $pdf=new TCPDF('L','mm','A4',true,'UTF-8'); $pdf->SetCreator('La Suriana'); $pdf->SetTitle($reporte['titulo']); $pdf->SetMargins(8,10,8); $pdf->SetAutoPageBreak(true,10); $pdf->setPrintHeader(false); $pdf->setFooterData([31,78,121],[110,120,130],'La Suriana','Reporte generado '.now()->format('d/m/Y H:i')); $pdf->AddPage(); $pdf->SetFont('dejavusans','',7);
        $html='<table cellpadding="5" cellspacing="0" width="100%"><tr><td bgcolor="#0F6CBD" color="#FFFFFF"><span style="font-size:16px;font-weight:bold">'.e($reporte['titulo']).'</span><br>'.e($reporte['sucursal']).'</td><td bgcolor="#0F6CBD" color="#FFFFFF" align="right">Periodo: '.e($reporte['desde']).' al '.e($reporte['hasta']).'<br>Generado por: '.e($reporte['generado_por']).'</td></tr></table><br><table cellpadding="4"><tr>';
        foreach($reporte['kpis'] as $nombre=>$valor)$html.='<td bgcolor="#EEF5FB"><b>'.e($nombre).'</b><br><span style="font-size:11px">'.e((string)$valor).'</span></td>'; $html.='</tr></table><br><table border="1" bordercolor="#D7E0E8" cellpadding="3"><thead><tr bgcolor="#1F4E78" color="#FFFFFF">';
        foreach($reporte['encabezados'] as $h)$html.='<th><b>'.e($h).'</b></th>'; $html.='</tr></thead><tbody>';
        foreach($reporte['rows'] as $index=>$row){$bg=$index%2===0?'#FFFFFF':'#F5F8FA';$html.='<tr bgcolor="'.$bg.'">';foreach((array)$row as $value)$html.='<td>'.e((string)($value??'—')).'</td>';$html.='</tr>';}$html.='</tbody></table>';
        $pdf->writeHTML($html,true,false,true,false,''); return $pdf->Output('reporte.pdf','S');
    }
}
