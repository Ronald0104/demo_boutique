<?php
defined('BASEPATH') OR exit('No direct script access allowed'); 

// use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Reader\Csv;
// use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class Reporte extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('admin_model');
        $this->load->model('reporte_model');
        $this->load->model('tienda_model');
        $this->load->model('inventario_model');
    }

    public function _remap($method, $params = array()){
        if ($this->admin_model->logged_id()) {
            if (method_exists($this, $method))
            {
                return call_user_func_array(array($this, $method), $params);
            }
            show_404();

        }
        else {
            redirect('');
        }
    }

    public function index() {
        echo "Reportes del sistema"; 
    }

    public function reporteFlujoCaja() {
        $usuario = $this->admin_model->logged_id();        
        if (!in_array($usuario['rol_id'], ["1","2"])){
            $page = "layouts/message";
            $mensaje = "NO TIENE AUTORIZACIÓN PARA ACCEDER A ESTE SITIO";
            $this->load->view('init', ['page'=>$page, 'mensaje'=>$mensaje]);
        } else {
            $fechaDesde = new DateTime();
            $diaSemana = $fechaDesde->format('w');
            if($diaSemana==0)
                $diaSemana=7;
             
            --$diaSemana;
            $interval = new DateInterval("P".$diaSemana."D"); $interval->invert = 1;
    
            $fechaDesde->add($interval);
            $fechaHasta = new DateTime();
            $usuario = $this->session->userdata('user');
            $tiendas = $this->tienda_model->getList();
            $tiendaUsuario = $usuario['tienda_sel'];
            $data = [
                'js' => ['rpt-flujo-caja.js'],
                'page' => '/reporte/reporte-flujo-caja',
                'fechaDesde' => $fechaDesde,            
                'fechaHasta' => $fechaHasta,
                'listaTiendas' => $tiendas,
                'tiendaUsuario' => $tiendaUsuario
            ];    
            $this->load->view('init', $data);
        }       
    }

    public function reporteFlujoCaja_DataJson(){
        
        $tienda = $this->input->post('tienda');
        $reporte = $this->reporte_model->reporteFlujoCaja($tienda);
        echo json_encode($reporte);
    }

    public function reporteFlujoCaja_Content(){
        $fechaDesde = $this->input->post('fechaDesde');
        $fechaHasta = $this->input->post('fechaHasta');
        $tiendaId = $this->input->post('tienda');
        $mostrarSaldoInicial = $this->input->post('mostrarSaldoInicial');
        
        $dia = substr($fechaDesde, 0, 2); $mes = substr($fechaDesde, 3, 2); $ano = substr($fechaDesde, 6, 4);
        $fechaDesde = new DateTime("$ano-$mes-$dia");
        $dia = substr($fechaHasta, 0, 2); $mes = substr($fechaHasta, 3, 2); $ano = substr($fechaHasta, 6, 4);
        $fechaHasta = new DateTime("$ano-$mes-$dia");
        $reporte = $this->reporte_model->reporteFlujoCaja($tiendaId,$fechaDesde,$fechaHasta,$mostrarSaldoInicial);
                
        $content = "";
        $i = 1;
        foreach ($reporte as $key => $value) {
            $fecha = new DateTime($value->fecha);   
            if (!$mostrarSaldoInicial && $key == 0) {
                continue;
            }
            $content .= "<tr tabindex='0'>";
            $content .= "<td>".($i++)."</td>";
            $content .= "<td>".$fecha->format('d-m-Y H:i')."</td>";
            $content .= "<td>$value->tipo</td>";
            if($value->nroOperacion==""){
                $content .= "<td><a href='#' class='link-operacion' data-tipo='".$value->tipo."' data-operacion='".$value->operacionId."'><u>".str_pad($value->operacionId, 6, '0', STR_PAD_LEFT)."</u></a></td>";
            } else {
                $content .= "<td><a href='#' class='link-operacion' data-tipo='".$value->tipo."' data-operacion='".$value->operacionId."'><u>".$value->nroOperacion."</u></a></td>";
            }
            // $content .= "<td>".str_pad($value->operacionId, 6, '0', STR_PAD_LEFT)."</td>";
            $content .= "<td>$value->descripcion</td>";
            if ($value->tipo == "SALDO INICIAL"){
                $content .= "<td class='text-right'>".number_format(0, 2, '.', ',')."</td>";
            } else {
                $content .= "<td class='text-right'>".number_format($value->monto, 2, '.', ',')."</td>";
            }
            $content .= "<td class='text-right font-weight-bold'>".number_format($value->saldoFinal, 2, '.', ',')."</td>";
            $content .= "</tr>";
        }
        echo $content;
    }

    public function reportePendienteDevolucion(){
        $usuario = $this->session->userdata('user');
        $tiendas = $this->tienda_model->getList();
        $tiendaUsuario = $usuario['tienda_sel'];
        $data = [
            'js' => ['rpt-pend-dev.js'],
            'page' => '/reporte/reporte-pendiente-devolucion',
            'listaTiendas' => $tiendas,
            'tiendaUsuario' => $tiendaUsuario
        ];

        $this->load->view('init', $data);
    }

    public function reportePendienteDevolucion_Content(){        
        $tiendas = $this->input->post('tienda');
        $diasVencidos = $this->input->post('diasVencidos');        
        $tiendas = str_replace("\"", "",implode(",", $tiendas));
        // $dia = substr($fechaDesde, 0, 2); $mes = substr($fechaDesde, 3, 2); $ano = substr($fechaDesde, 6, 4);
        // $fechaDesde = new DateTime("$ano-$mes-$dia");
        // $dia = substr($fechaHasta, 0, 2); $mes = substr($fechaHasta, 3, 2); $ano = substr($fechaHasta, 6, 4);
        // $fechaHasta = new DateTime("$ano-$mes-$dia");
        $reporte = $this->reporte_model->reportePendienteDevolucion($tiendas, $diasVencidos);
                
        $content = "";
        $i = 1;
        foreach ($reporte as $key => $value) {           
            $content .= "<tr tabindex='0'>";
            $content .= "<td>".($i++)."</td>";
            $content .= "<td><a href='#' class='link-cliente' data-cliente='".$value->clienteId."' data-cliente-nro='".$value->nro_documento."'><u>".$value->nro_documento."</u></a></td>";
            $content .= "<td>".$value->cliente."</td>";
            $content .= "<td>".$value->telefono."</td>";     
            if($value->ventaCode==""){
                $content .= "<td><a href='#' class='link-venta' data-venta='".$value->ventaId."'><u>".$value->ventaCode0."</u></a></td>";
            }else {
                $content .= "<td><a href='#' class='link-venta' data-venta='".$value->ventaId."'><u>".$value->ventaCode."</u></a></td>";
            }   
            $content .= "<td>".$value->tienda."</td>";
            $content .= "<td>".$value->fechaSalida."</td>";
            $content .= "<td>".$value->fechaDevolucionProg."</td>";
            if($value->diasVencidos < 0){
                $content .= "<td>FALTAN ".abs($value->diasVencidos)." DÍA(S)</td>";
            }else {
                $content .= "<td>".$value->diasVencidos." DÍA(S) VENCIDOS</td>";
            }
            $content .= "</tr>";
        }
        echo $content;
    }

    public function reporteReservas(){
        $fechaDesde = new DateTime();
        // $diaSemana = $fechaDesde->format('w');
        // if($diaSemana==0)
        //     $diaSemana=7;
         
        // --$diaSemana;
        // $interval = new DateInterval("P".$diaSemana."D"); $interval->invert = 1;

        // $fechaDesde->add($interval);
        $fechaHasta = new DateTime();
        $fechaHasta->add(new DateInterval("P7D"));
        $usuario = $this->session->userdata('user');
        $tiendas = $this->tienda_model->getList();
        $tiendaUsuario = $usuario['tienda_sel'];
        $data = [
            'js' => ['/reports/rpt-reservas.js?v=1.1'],
            'page' => '/reporte/reporte-reservas',
            'listaTiendas' => $tiendas,
            'tiendaUsuario' => $tiendaUsuario,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta
        ];

        $this->load->view('init', $data);
    }

    public function reporteReservas_Content(){  
        $fechaDesde = $this->input->post('fechaDesde');
        $fechaHasta = $this->input->post('fechaHasta'); 
        $dia = substr($fechaDesde, 0, 2); $mes = substr($fechaDesde, 3, 2); $ano = substr($fechaDesde, 6, 4);
        $fechaDesde = new DateTime("$ano-$mes-$dia");
        $dia = substr($fechaHasta, 0, 2); $mes = substr($fechaHasta, 3, 2); $ano = substr($fechaHasta, 6, 4);
        $fechaHasta = new DateTime("$ano-$mes-$dia");

        $tiendas = $this->input->post('tienda');
        $diasFaltantes = $this->input->post('diasFaltantes'); 
        $mostrarDetallado =   $this->input->post('mostrarDetallado');      
        $tiendas = str_replace("\"", "",implode(",", $tiendas));
        $reporte = $this->reporte_model->reporteReservas($tiendas, $fechaDesde, $fechaHasta, $diasFaltantes, $mostrarDetallado);
                
        $content = "";
        $i = 1;
        foreach ($reporte as $key => $value) {           
            $content .= "<tr tabindex='0'>";
            $content .= "<td>".($i++)."</td>";
            $content .= "<td><a href='#' class='link-cliente' data-cliente='".$value->clienteId."' data-cliente-nro='".$value->nro_documento."'><u>".$value->nro_documento."</u></a></td>";
            $content .= "<td>".$value->cliente."</td>";
            if ($value->ventaCode == "") {
                $content .= "<td><a href='#' class='link-venta' data-venta='".$value->ventaId."'><u>".$value->ventaCode0."</u></a></td>";
            } else {
                $content .= "<td><a href='#' class='link-venta' data-venta='".$value->ventaId."'><u>".$value->ventaCode."</u></a></td>";
            }
            $content .= "<td>".$value->tienda."</td>";
            $content .= "<td>".$value->fechaRegistro."</td>";
            $content .= "<td>".$value->fechaReserva."</td>";
            $content .= "<td>".$value->diasReserva."</td>";
            if ($mostrarDetallado == 1) {
                $content .= "<td>".$value->code."</td>";
                $content .= "<td>".$value->nombreArticulo."</td>";
            }
            $content .= "</tr>";
        }
        echo $content;
    }

    public function reporteReservas_Excel($mostrarDetallado = 0, $tiendas = "", $fechaDesde = "", $fechaHasta = "")
    {
        //$this->reporteReservas_Content();
        // header("Pragma: public");
        // header("Expires: 0");
        // $filename = "reporte-reservas.xls";
        // header("Content-type: application/x-msdownload");
        // header("Content-Disposition: attachment; filename=$filename");
        // header("Pragma: no-cache");
        // header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

        // $archivo = $_SERVER['DOCUMENT_ROOT'] . "/assets/files/plantilla-inventario.xlsx";
        // header('Content-Disposition: attachment; filename=' . $archivo);
        // header("Content-Type: application/vnd.openxmlformats-   officedocument.spreadsheetml.sheet");
        // header('Cache-Control: must-revalidate, post-check=0, pre-check=0');



        // $fecha = date("d-m-Y H:i:s");
        // header("Content-Disposition: attachment; filename=reporte-reservas-$fecha.xlsx");
        // header('Content-Type: application/vnd.ms-excel');
        // // header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // // header('Content-Length: '.fipplication/vnd.openxmlformats-officedocument.spreadsheetml.sheet
        // header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        // header("Pragma: no-cache");
        // header("Expires: 0");

        //$fechaDesde = $this->input->post('fechaDesde');
        //$fechaHasta = $this->input->post('fechaHasta'); 

        $fechaDesde = ($fechaDesde=="") ? date("Y-m-d") : $fechaDesde;
        $fechaDesde = new DateTime($fechaDesde);
        $fechaHasta = ($fechaHasta=="") ? date("Y-m-d") : $fechaHasta;
        $fechaHasta = new DateTime($fechaHasta);
        $tiendas = str_replace('-',',',$tiendas);

        // $dia = substr($fechaDesde, 0, 2); $mes = substr($fechaDesde, 3, 2); $ano = substr($fechaDesde, 6, 4);
        // $fechaDesde = new DateTime("$ano-$mes-$dia");
        // $dia = substr($fechaHasta, 0, 2); $mes = substr($fechaHasta, 3, 2); $ano = substr($fechaHasta, 6, 4);
        // $fechaHasta = new DateTime("$ano-$mes-$dia");

        // // $tiendas = $this->input->post('tienda');
        // // $diasFaltantes = $this->input->post('diasFaltantes'); 
        // // $mostrarDetallado =   $this->input->post('mostrarDetallado');    
        
        // $tiendas = implode(",",[$tiendaId]);
        $diasFaltantes = "";
        // $tiendas = str_replace("\"", "",implode(",", $tiendas));
        $reporte = $this->reporte_model->reporteReservas($tiendas, $fechaDesde, $fechaHasta, $diasFaltantes, $mostrarDetallado);
    
           
        // $content = "";
        // $i = 1;
        // $content .= "<table>";
        // foreach ($reporte as $key => $value) {           
        //     $content .= "<tr tabindex='0'>";
        //     $content .= "<td>".($i++)."</td>";
        //     $content .= "<td><a href='#' class='link-cliente' data-cliente='".$value->clienteId."' data-cliente-nro='".$value->nro_documento."'><u>".$value->nro_documento."</u></a></td>";
        //     $content .= "<td>".$value->cliente."</td>";
        //     if ($value->ventaCode == "") {
        //         $content .= "<td><a href='#' class='link-venta' data-venta='".$value->ventaId."'><u>".$value->ventaCode0."</u></a></td>";
        //     } else {
        //         $content .= "<td><a href='#' class='link-venta' data-venta='".$value->ventaId."'><u>".$value->ventaCode."</u></a></td>";
        //     }
        //     $content .= "<td>".$value->tienda."</td>";
        //     $content .= "<td>".$value->fechaRegistro."</td>";
        //     $content .= "<td>".$value->fechaReserva."</td>";
        //     $content .= "<td>".$value->diasReserva."</td>";
        //     if ($mostrarDetallado == 1) {
        //         $content .= "<td>".$value->code."</td>";
        //         $content .= "<td>".$value->nombreArticulo."</td>";
        //     }
        //     $content .= "</tr>";
        // }
        // $content .= "</table>";
        // echo $content;


        $this->load->library('excel');
        $this->excel->setActiveSheetIndex(0);
        $this->excel->getActiveSheet()->setTitle('Reservas');

        // Datos generales del reporte
        $this->excel->getActiveSheet()->getStyle("E2")->getFont()->setBold(true)->setSize(16);        
        $this->excel->getActiveSheet()->setCellValue("E2", 'REPORTE RESERVAS');
        $this->excel->getActiveSheet()->getStyle("B3")->getFont()->setBold(true);
        $this->excel->getActiveSheet()->setCellValue("B3", 'Fecha Desde');
        $this->excel->getActiveSheet()->getStyle("B4")->getFont()->setBold(true);
        $this->excel->getActiveSheet()->setCellValue("B4", 'Fecha Hasta');
        $this->excel->getActiveSheet()->setCellValue("C3", $fechaDesde->format('d-m-Y'));
        $this->excel->getActiveSheet()->setCellValue("C4", $fechaDesde->format('d-m-Y'));

        $contador = 6;
        //Le aplicamos ancho las columnas.
        $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(14);
        $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(40);
        $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(16);
        $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        if($mostrarDetallado == 1) {
            $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(50);
        }
        
        //Le aplicamos negrita a los títulos de la cabecera.
        $this->excel->getActiveSheet()->getStyle("A{$contador}")->getFont()->setBold(true);
        $this->excel->getActiveSheet()->getStyle("B{$contador}")->getFont()->setBold(true);
        $this->excel->getActiveSheet()->getStyle("C{$contador}")->getFont()->setBold(true);
        $this->excel->getActiveSheet()->getStyle("D{$contador}")->getFont()->setBold(true);
        $this->excel->getActiveSheet()->getStyle("E{$contador}")->getFont()->setBold(true);
        $this->excel->getActiveSheet()->getStyle("F{$contador}")->getFont()->setBold(true);
        $this->excel->getActiveSheet()->getStyle("G{$contador}")->getFont()->setBold(true);
        $this->excel->getActiveSheet()->getStyle("H{$contador}")->getFont()->setBold(true);
        if($mostrarDetallado == 1) {
            $this->excel->getActiveSheet()->getStyle("I{$contador}")->getFont()->setBold(true);
            $this->excel->getActiveSheet()->getStyle("J{$contador}")->getFont()->setBold(true);  
        }
        //Definimos los títulos de la cabecera.
        $this->excel->getActiveSheet()->setCellValue("A{$contador}", '#');
        $this->excel->getActiveSheet()->setCellValue("B{$contador}", 'DNI');
        $this->excel->getActiveSheet()->setCellValue("C{$contador}", 'Cliente');
        $this->excel->getActiveSheet()->setCellValue("D{$contador}", 'N° Operación');
        $this->excel->getActiveSheet()->setCellValue("E{$contador}", 'Tienda');
        $this->excel->getActiveSheet()->setCellValue("F{$contador}", 'Fecha Registro');
        $this->excel->getActiveSheet()->setCellValue("G{$contador}", 'Fecha Reserva');
        $this->excel->getActiveSheet()->setCellValue("H{$contador}", 'Dias Faltantes');
        if($mostrarDetallado == 1) {
            $this->excel->getActiveSheet()->setCellValue("I{$contador}", 'Código');
            $this->excel->getActiveSheet()->setCellValue("J{$contador}", 'Descripción');
        }
        //Definimos la data del cuerpo.    
        foreach ($reporte as $key => $value) {
            $contador++;
            $this->excel->getActiveSheet()->setCellValue("A{$contador}", $key+1);
            $this->excel->getActiveSheet()->setCellValue("B{$contador}", $value->nro_documento);
            $this->excel->getActiveSheet()->setCellValue("C{$contador}", $value->cliente);
            $this->excel->getActiveSheet()->setCellValue("D{$contador}", $value->ventaCode);
            $this->excel->getActiveSheet()->setCellValue("E{$contador}", $value->tienda);
            $this->excel->getActiveSheet()->setCellValue("F{$contador}", $value->fechaRegistro);
            $this->excel->getActiveSheet()->setCellValue("G{$contador}", $value->fechaReserva);
            $this->excel->getActiveSheet()->setCellValue("H{$contador}", $value->diasReserva);
            if($mostrarDetallado == 1) {
                $this->excel->getActiveSheet()->setCellValue("I{$contador}", $value->code);
                $this->excel->getActiveSheet()->setCellValue("J{$contador}", $value->nombreArticulo);
            }
        }

        $column_end = "H";
        if($mostrarDetallado == 1) {
            $column_end = "J";
        }
        $this->excel->getActiveSheet()->getStyle("A6:{$column_end}{$contador}")->applyFromArray(
            array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('rgb' => '000')
                    )
                )
            )
        );
        
        // allborders
        // $border_style= array('borders' => array('right' => array('style' => 
        // PHPExcel_Style_Border::BORDER_THICK,'color' => array('argb' => '766f6e'),)));

        // $sheet = $objPHPExcel->getActiveSheet();
        // $sheet->getStyle("A2:A40")->applyFromArray($border_style);

        //Le ponemos un nombre al archivo que se va a generar.
        $fecha_actual = date('dmY');
        $archivo = "REPORTE_RESERVAS_{$fecha_actual}.xls";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$archivo.'"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
        //Hacemos una salida al navegador con el archivo Excel.
        $objWriter->save('php://output');

        
        /* Data */
        // $data = $this->home_model->fetch_transactions();

        /* Spreadsheet Init */
        // $spreadsheet = new Spreadsheet();
        // $sheet = $spreadsheet->getActiveSheet();
  
        /* Excel Header */
        // $sheet->setCellValue('A1', '#');
        // $sheet->setCellValue('B1', 'Name');
        // $sheet->setCellValue('C1', 'Price');
        // $sheet->setCellValue('D1', 'Quantity');
        // $sheet->setCellValue('E1', 'Total');
        // $sheet->setCellValue('F1', 'Date');
          
        // /* Excel Data */
        // $row_number = 2;
        // foreach($data as $key => $row)
        // {
        //     $sheet->setCellValue('A'.$row_number, $key+1);
        //     $sheet->setCellValue('B'.$row_number, $row['name']);
        //     $sheet->setCellValue('C'.$row_number, $row['price']);
        //     $sheet->setCellValue('D'.$row_number, $row['qty']);
        //     $sheet->setCellValue('E'.$row_number, $row['total']);
        //     $sheet->setCellValue('F'.$row_number, $row['input_date']);
        
        //     $row_number++;
        // }
  
          /* Excel File Format */
        //   $writer = new Xlsx($spreadsheet);
        //   $filename = 'excel-report';
          
        //   header('Content-Type: application/vnd.ms-excel');
        //   header('Content-Disposition: attachment;filename="'. $filename .'.xlsx"'); 
        //   header('Cache-Control: max-age=0');
  
        //   $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        //   $writer->save('php://output');
    }

    public function reporteReservasTest($id = 0) {
        echo "Tienda $id";
    }
    public function reporteSaldosPendientes() {
        $fechaDesde = new DateTime();
        $diaSemana = $fechaDesde->format('w');
        if($diaSemana==0) $diaSemana=7;
         
        --$diaSemana;
        $interval = new DateInterval("P".$diaSemana."D"); $interval->invert = 1;

        $fechaDesde->add($interval);
        $fechaHasta = new DateTime();
        $usuario = $this->session->userdata('user');
        $tiendas = $this->tienda_model->getList();
        $tiendaUsuario = $usuario['tienda_sel'];
        $data = [
            'js' => ['rpt-saldos-pend.js'],
            'page' => '/reporte/reporte-saldos-pendientes',
            'fechaDesde' => $fechaDesde,            
            'fechaHasta' => $fechaHasta,
            'listaTiendas' => $tiendas,
            'tiendaUsuario' => $tiendaUsuario
        ];

        $this->load->view('init', $data);
    }

    public function reporteSaldosPendientes_Content(){  
        $fechaDesde = $this->input->post('fechaDesde');
        $fechaHasta = $this->input->post('fechaHasta'); 

        // $fecha = new DateTime();
        // $dia = date("d"); $mes = date("M"); $ano = date("Y");
        $dia = substr($fechaDesde, 0, 2); $mes = substr($fechaDesde, 3, 2); $ano = substr($fechaDesde, 6, 4);
        $fechaDesde = new DateTime("$ano-$mes-$dia");
        $dia = substr($fechaHasta, 0, 2); $mes = substr($fechaHasta, 3, 2); $ano = substr($fechaHasta, 6, 4);
        $fechaHasta = new DateTime("$ano-$mes-$dia");

        $tiendas = $this->input->post('tienda');
        // $diasFaltantes = $this->input->post('diasFaltantes'); 
        // $mostrarDetallado =   $this->input->post('mostrarDetallado');      
        $tiendas = str_replace("\"", "",implode(",", $tiendas));
        $reporte = $this->reporte_model->reporteSaldosPendientes($tiendas, $fechaDesde, $fechaHasta);
                
        $content = "";
        $i = 1;
        foreach ($reporte as $key => $value) {           
            $content .= "<tr tabindex='0'>";
            $content .= "<td>".($i++)."</td>";
            $content .= "<td><a href='#' class='link-cliente' data-cliente='".$value->clienteId."' data-cliente-nro='".$value->nroDocumento."'><u>".$value->nroDocumento."</u></a></td>";
            $content .= "<td>".$value->cliente."</td>";
            $content .= "<td><a href='#' class='link-venta' data-venta='".$value->ventaId."'><u>".$value->codigo."</u></a></td>";
            $content .= "<td>".$value->estado."</td>";
            $content .= "<td>".$value->tienda."</td>";
            $content .= "<td>".$value->fechaRegistro."</td>";
            $content .= "<td>".$value->fechaSalida."</td>";
            $content .= "<td>".$value->fechaDevolucion."</td>";
            $content .= "<td>".$value->precioTotal."</td>";
            $content .= "<td>".$value->totalSaldo."</td>";
            $content .= "</tr>";
        }
        echo $content;
    }

    public function reportePrendas(){
        $fechaDesde = new DateTime();
        // $diaSemana = $fechaDesde->format('w');
        // if($diaSemana==0) $diaSemana=7;         
        // --$diaSemana;
        // $interval = new DateInterval("P".$diaSemana."D"); $interval->invert = 1;

        // $fechaDesde->add($interval);
        $fechaHasta = new DateTime();
        $usuario = $this->session->userdata('user');
        $tiendas = $this->tienda_model->getList();
        $tiendaUsuario = $usuario['tienda_sel'];
        $categorias = $this->inventario_model->getListCategorias();
        $data = [
            'js' => ['rpt-prendas.js'],
            'page' => '/reporte/reporte-prendas',
            'fechaDesde' => $fechaDesde,            
            'fechaHasta' => $fechaHasta,
            'listaTiendas' => $tiendas,
            'tiendaUsuario' => $tiendaUsuario,
            'categorias' => $categorias
        ];

        $this->load->view('init', $data);
    }

    public function reportePrendas_Content(){        
        $categoriaId = $this->input->post('categoria');
        $tallas = $this->input->post('talla');  
        if ($tallas == null) {
            $tallas = 0;
        } else {               
            $tallas = str_replace("\"", "",implode(",", $tallas));   
        }        
        $colores = $this->input->post('color');   
        if ($colores == null) {
            $colores = 0;
        } else {
            $colores = str_replace("\"", "",implode(",", $colores));
        }
        $disenos = $this->input->post('diseno');        
        if ($disenos == null) {
            $disenos = 0;
        } else {
            $disenos = str_replace("\"", "",implode(",", $disenos));
        }
        $condicion = $this->input->post('condicion');

        $fechaDesde = $this->input->post('fechaDesde');
        $fechaHasta = $this->input->post('fechaHasta'); 
        $dia = substr($fechaDesde, 0, 2); $mes = substr($fechaDesde, 3, 2); $ano = substr($fechaDesde, 6, 4);
        $fechaDesde = new DateTime("$ano-$mes-$dia");
        $dia = substr($fechaHasta, 0, 2); $mes = substr($fechaHasta, 3, 2); $ano = substr($fechaHasta, 6, 4);
        $fechaHasta = new DateTime("$ano-$mes-$dia");
        $reporte = $this->reporte_model->reportePrendasDisponibles($categoriaId, $fechaDesde, $fechaHasta, $tallas, $colores, $disenos, $condicion);
                
        $content = "";
        $i = 1;
        foreach ($reporte as $key => $value) {           
            $content .= "<tr tabindex='0'>";
            $content .= "<td>".($i++)."</td>";
            // $content .= "<td><a href='#' class='link-cliente' data-cliente='".$value->clienteId."' data-cliente-nro='".$value->nro_documento."'><u>".$value->nro_documento."</u></a></td>";
            $content .= "<td>".$value->code."</td>";
            $content .= "<td>".$value->nombre."</td>";
            // $content .= "<td><a href='#' class='link-venta' data-venta='".$value->ventaId."'><u>".$value->ventaCode."</u></a></td>";
            $content .= "<td>".$value->categoria."</td>";
            $content .= "<td>".$value->estado."</td>";
            $content .= "<td>".$value->condicion."</td>";
            $content .= "<td>".$value->talla."</td>";
            $content .= "<td>".$value->color."</td>";
            $content .= "<td>".$value->diseno."</td>";
            $content .= "</tr>";
        }
        echo $content;
    }

    public function reporteTopClientes(){
        $fechaDesde = new DateTime();
        $diaSemana = $fechaDesde->format('w');
        // if($diaSemana==0) $diaSemana=7;         
        // --$diaSemana;
        $interval = new DateInterval("P1Y"); $interval->invert = 1;
        $fechaDesde->add($interval);

        $fechaHasta = new DateTime();
        $usuario = $this->session->userdata('user');
        $tiendas = $this->tienda_model->getList();
        $tiendaUsuario = $usuario['tienda_sel'];
        $data = [
            'js' => ['rpt-top-clientes.js'],
            'page' => '/reporte/reporte-top-clientes',
            'fechaDesde' => $fechaDesde,            
            'fechaHasta' => $fechaHasta,
            'listaTiendas' => $tiendas,
            'tiendaUsuario' => $tiendaUsuario
        ];

        $this->load->view('init', $data);
    }

    public function reporteTopClientes_Content() {
        $cantidadMostrar = $this->input->post('cantidadMostrar');
        $fechaDesde = $this->input->post('fechaDesde');
        $fechaHasta = $this->input->post('fechaHasta'); 
        $dia = substr($fechaDesde, 0, 2); $mes = substr($fechaDesde, 3, 2); $ano = substr($fechaDesde, 6, 4);
        $fechaDesde = new DateTime("$ano-$mes-$dia");
        $dia = substr($fechaHasta, 0, 2); $mes = substr($fechaHasta, 3, 2); $ano = substr($fechaHasta, 6, 4);
        $fechaHasta = new DateTime("$ano-$mes-$dia");
        $reporte = $this->reporte_model->ReporteTopClientes($cantidadMostrar, $fechaDesde, $fechaHasta);
                
        $content = "";
        $i = 1;
        foreach ($reporte as $key => $value) {           
            $content .= "<tr tabindex='0'>";
            $content .= "<td>".($i++)."</td>";
            $content .= "<td><a href='#' class='link-cliente' data-cliente='".$value->clienteId."' data-cliente-nro='".$value->nroDocumento."'><u>".$value->nroDocumento."</u></a></td>";
            // $content .= "<td>".$value->nroDocumento."</td>";
            $content .= "<td>".$value->nombresApellidos."</td>";
            // $content .= "<td><a href='#' class='link-venta' data-venta='".$value->ventaId."'><u>".$value->ventaCode."</u></a></td>";
            $content .= "<td>".$value->fechaUltimaCompra."</td>";
            $content .= "<td>".$value->cantidad."</td>";
            $content .= "<td>".$value->total."</td>";    
            $content .= "</tr>";
        }
        echo $content;
    }

    public function reporteTopProductos() {
        $fechaDesde = new DateTime();
        $diaSemana = $fechaDesde->format('w');
        $interval = new DateInterval("P1Y"); $interval->invert = 1;
        $fechaDesde->add($interval);

        $fechaHasta = new DateTime();
        $usuario = $this->session->userdata('user');
        $tiendas = $this->tienda_model->getList();
        $tiendaUsuario = $usuario['tienda_sel'];
        $categorias = $this->inventario_model->getListCategorias();
        $data = [
            'js' => ['rpt-top-productos.js'],
            'page' => '/reporte/reporte-top-productos',
            'fechaDesde' => $fechaDesde,            
            'fechaHasta' => $fechaHasta,
            'categorias' => $categorias,
            'listaTiendas' => $tiendas,
            'tiendaUsuario' => $tiendaUsuario
        ];

        $this->load->view('init', $data);
    }

    public function reporteTopProductos_Content(){
        $cantidadMostrar = $this->input->post('cantidadMostrar');
        $fechaDesde = $this->input->post('fechaDesde');
        $fechaHasta = $this->input->post('fechaHasta'); 
        $dia = substr($fechaDesde, 0, 2); $mes = substr($fechaDesde, 3, 2); $ano = substr($fechaDesde, 6, 4);
        $fechaDesde = new DateTime("$ano-$mes-$dia");
        $dia = substr($fechaHasta, 0, 2); $mes = substr($fechaHasta, 3, 2); $ano = substr($fechaHasta, 6, 4);
        $fechaHasta = new DateTime("$ano-$mes-$dia");
        $categoriaId = $this->input->post('categoria');
        $tallas = $this->input->post('talla');  
        if ($tallas == null) {
            $tallas = 0;
        } else {               
            $tallas = str_replace("\"", "",implode(",", $tallas));   
        }        
        $colores = $this->input->post('color');   
        if ($colores == null) {
            $colores = 0;
        } else {
            $colores = str_replace("\"", "",implode(",", $colores));
        }
        $disenos = $this->input->post('diseno');        
        if ($disenos == null) {
            $disenos = 0;
        } else {
            $disenos = str_replace("\"", "",implode(",", $disenos));
        }        

        $reporte = $this->reporte_model->ReporteTopProductos($cantidadMostrar, $fechaDesde, $fechaHasta, $categoriaId, $tallas, $colores, $disenos);
        
        $content = "";
        $i = 1;
        foreach ($reporte as $key => $value) {           
            $content .= "<tr tabindex='0'>";
            $content .= "<td>".$value->item."</td>";            
            // $content .= "<td>".$value->code."</td>";
            $content .= "<td><a href='#' class='link-producto' data-producto='".$value->articuloId."'><u>".$value->code."</u></a></td>";
            $content .= "<td>".$value->nombreArticulo."</td>";
            $content .= "<td>".$value->caracteristicas."</td>";
            $content .= "<td>".$value->categoria."</td>";
            $content .= "<td>".$value->estado."</td>";
            // $content .= "<td><a href='#' class='link-cliente' data-cliente='".$value->clienteId."' data-cliente-nro='".$value->nroDocumento."'><u>".$value->nroDocumento."</u></a></td>";
            // // $content .= "<td>".$value->nroDocumento."</td>";
            // $content .= "<td>".$value->nombresApellidos."</td>";
            // // $content .= "<td><a href='#' class='link-venta' data-venta='".$value->ventaId."'><u>".$value->ventaCode."</u></a></td>";
            // $content .= "<td>".$value->fechaUltimaCompra."</td>";
            $content .= "<td>".$value->cantidad."</td>";
            $content .= "<td>".$value->total."</td>";    
            $content .= "</tr>";
        }
        echo $content;
    }
            
}
