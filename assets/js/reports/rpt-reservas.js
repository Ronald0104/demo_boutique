$(function() {
    $('#fechaDesde').datepicker({
        dateFormat: 'dd/mm/yy',
        minDate: '-2M',
        maxDate: '+12M',
        showButtonPanel: true,
		showOtherMonths: true,
		selectOtherMonths: true
    });
    $('#fechaHasta').datepicker({
        dateFormat: 'dd/mm/yy',
        minDate: '-2M',
        maxDate: '+12M',
        showButtonPanel: true,
		showOtherMonths: true,
		selectOtherMonths: true
    });
    
    $('#tienda').select2({minimumResultsForSearch: Infinity})
    // $('#diasFaltantes').select2({minimumResultsForSearch: Infinity})

    $('#tienda').on('change', function (e) {
        construirURL();
    });
    $('#fechaDesde').on("change", function(e) {
        // console.log(e.target);
        construirURL();
    })
    $('#fechaHasta').on("change", function(e) {
        // console.log(e.target);
        construirURL();
    })

    $('#btn-mostrar-reporte').click(function(evt) {
        evt.preventDefault();
        fn_ReporteReservas();    
    })

    $('#btn-exportar-reporte').click(function(evt) {
        // evt.preventDefault();
        // fn_ExportarReservas();   
        let tiendaId = $('#tienda').val();
        tiendaId = tiendaId[0];
        console.log(tiendaId);
        // var $this = $(this);
        // console.log($this);
        // $.ajax({
        //     url: `/generar_excel/${tiendaId}`,
        //     async: false,
        //     success: function (url) {
        //         console.log(url);
        //         $this.attr("href", url);
        //         $this.attr("target", "_blank");
        //     },
        //     error: function () {
        //         evt.preventDefault();
        //     }
        // }); 

        let currentDate = new Date();
        let fileName = "REPORTE_RESERVAS_"+[
            padTo2Digits(currentDate.getDate()),
            padTo2Digits(currentDate.getMonth() + 1),
            currentDate.getFullYear(),
          ].join('/')+".xls";

        // Descargar un archivo ya generado desde un punto de acceso
        $.ajax({
            type: 'GET',
            url: `/generar_excel/${tiendaId}`,
            cache: false,
            xhr: function () {
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 2) {
                        if (xhr.status == 200) {
                            xhr.responseType = "blob";
                        } else {
                            xhr.responseType = "text";
                        }
                    }
                };
                return xhr;
            },
            // async: false,
            success: function (data, status, xhr) {
                //Convert the Byte Data to BLOB object.
                var blob = new Blob([data], { type: "application/octetstream" });
                
                //Check the Browser type and download the File.
                var isIE = false || !!document.documentMode;
                if (isIE) {
                    window.navigator.msSaveBlob(blob, fileName);
                } else {
                    var url = window.URL || window.webkitURL;
                    link = url.createObjectURL(blob);
                    var a = $("<a />");
                    a.attr("download", fileName);
                    a.attr("href", link);
                    $("body").append(a);
                    a[0].click();
                    $("body").remove(a);
                }
            },
            error: function (error) {
                console.log(error);
            }
        }); 
        //////////////////////////////////////////////////


    })

    // $('#btn-exportar-reporte').click(function() {
    //     console.log('Exportar Excel');
    //     if (!exportTable) return;
    //     // exportTable.getExportData()[''];

    //     var tableId = 'rptReservas';
    //     var XLS = exportTable.CONSTANTS.FORMAT.XLSX;
    //     console.log(XLS);

    //     var exportData = exportTable.getExportData()[tableId][XLS];
    //     console.log(exportData);
    //     console.log(exportData.data);
    //     exportTable.export2file(
    //         exportData.data,
    //         exportData.mimeType,
    //         exportData.filename,
    //         exportData.fileExtension
    //     );
    // })

    $(document).on('click', '.link-cliente', function (evt) {
        evt.preventDefault();
        let clienteId = $(this).data('cliente');
        let nroDocumento = $(this).data('cliente-nro');
        fn_ObtenerModalRegistrarCliente(function () {
            fn_LimpiarCliente();
            $('#customerId_Add').val(clienteId);
            $('#nroDocumento_Add').val(nroDocumento);
            $('#nroDocumento_Add').data('valueOld', nroDocumento);
            fn_ObtenerCliente(() => $('#modal-register-customer').modal('show'));
        })
    })
    $(document).on('click', '.link-venta', function (evt) {
        evt.preventDefault();
        let ventaId = $(this).data('venta');
        Call_Progress(true)
        setTimeout(function () {
            Call_Progress(true);
            window.open('/ventas/editar/' + ventaId, '_blank');
            Call_Progress(false);
        }, 1000)
    });

    $('#mostrarDetallado').change(function() {
        var mostrar = Number($(this).prop('checked'));       
        if (mostrar) {
            $('#rptReservas>thead>tr>th').eq(8).css('visibility', 'visible');
            $('#rptReservas>thead>tr>th').eq(9).css('visibility', 'visible');
        } else {
            $('#rptReservas>thead>tr>th').eq(8).css('visibility', 'hidden');
            $('#rptReservas>thead>tr>th').eq(9).css('visibility', 'hidden');
        }
        fn_ReporteReservas();
        construirURL();
    })

    fn_ReporteReservas();
    construirURL();
})

function padTo2Digits(num) {
    return num.toString().padStart(2, '0');
}

function fn_ReporteReservas() {
    let tienda = $('#tienda').val();
    let fechaDesde = $('#fechaDesde').val();
    let fechaHasta = $('#fechaHasta').val();
    let diasFaltantes = $('#diasFaltantes').val();
    let mostrarDetallado = Number($('#mostrarDetallado').prop('checked'));

    $.ajax({
        method: 'POST',
        url: '/reporte/reporteReservas_Content',
        data: {tienda: tienda, fechaDesde: fechaDesde, fechaHasta : fechaHasta, diasFaltantes: diasFaltantes, mostrarDetallado: mostrarDetallado},
        beforeSend: Call_Progress(true)
    })
    .done(function(data) {
        $('#tbl_reporte').html('');
        $('#tbl_reporte').html(data);
        fn_ExportTable();
    })
    .fail(function(jqXHR, textStatus){
        console.log(jqXHR.responseText);
    })
    .always(function() {
        Call_Progress(false)
    })
}

function fn_ExportarReservas() {
    let tienda = $('#tienda').val();
    let fechaDesde = $('#fechaDesde').val();
    let fechaHasta = $('#fechaHasta').val();
    let diasFaltantes = $('#diasFaltantes').val();
    let mostrarDetallado = Number($('#mostrarDetallado').prop('checked'));

    console.log(fechaDesde);
    console.log(tienda);
    console.log(diasFaltantes);
    console.log(mostrarDetallado);

    let data = {tienda: tienda, fechaDesde: fechaDesde, fechaHasta : fechaHasta, diasFaltantes: diasFaltantes, mostrarDetallado: mostrarDetallado};
    // DownloadFile("reporte-reservas.xlsx", data);
    $.ajax({
        method: 'POST',
        url: '/reporte/reporteReservas_Excel/',
        async: false
        // data: {tienda: tienda, fechaDesde: fechaDesde, fechaHasta : fechaHasta, diasFaltantes: diasFaltantes, mostrarDetallado: mostrarDetallado},
        // beforeSend: Call_Progress(true)
    })
    .done(function(data) {
        // console.log("data");
        // $('#tbl_reporte').html('');
        // $('#tbl_reporte').html(data);
        // fn_ExportTable();
    })
    .fail(function(jqXHR, textStatus){
        console.log(jqXHR.responseText);
    })
    .always(function() {
        // Call_Progress(false)
    })
}

var exportTable;
function fn_ExportTable() {
    exportTable = $("#rptReservas").tableExport({
        formats: ["xlsx","txt"], //Tipo de archivos a exportar ("xlsx","txt", "csv", "xls")
        position: 'bottom',  // Posicion que se muestran los botones puedes ser: (top, bottom)
        bootstrap: true,//Usar lo estilos de css de bootstrap para los botones (true, false)
        fileName: "ReporteReservas",    //Nombre del archivo 
        exportButtons: false    
    });    
}

function DownloadFile(fileName, data) {
    
    // var url = "Files/" + fileName;
    var url = "/reporte/reporteReservas_Excel";
    $.ajax({
        method: 'POST',
        url: url,
        data: data,
        cache: false,
        xhr: function () {
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 2) {
                    if (xhr.status == 200) {
                        xhr.responseType = "blob";
                    } else {
                        xhr.responseType = "text";
                    }
                }
            };
            return xhr;
        },
        success: function (data) {
            // console.log(data);
            // //Convert the Byte Data to BLOB object.
            var blob = new Blob([data], { type: "application/octetstream" });
            // var blob = new Blob([data], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });

            // //Check the Browser type and download the File.
            var isIE = false || !!document.documentMode;
            if (isIE) {
                console.log('xD!! 1111');
                window.navigator.msSaveBlob(blob, fileName);
            } else {
                console.log('xD!! 2222');
                var url = window.URL || window.webkitURL;
                link = url.createObjectURL(blob);
                var a = $("<a />");
                a.attr("download", fileName);
                a.attr("href", link);
                $("body").append(a);
                a[0].click();
                $("body").remove(a);
            }
        }
    });
};

function construirURL() {
    let link = $("#btn-exportar-reporte");
    let tiendas = $('#tienda').val().join('-');
    let fechaDesde = $('#fechaDesde').val();
    let fechaHasta = $('#fechaHasta').val();
    let mostrarDetallado = Number($('#mostrarDetallado').prop('checked'));

    fechaDesde = `${fechaDesde.substr(6,4)}-${fechaDesde.substr(3,2)}-${fechaDesde.substr(0,2)}`;
    fechaHasta = `${fechaHasta.substr(6,4)}-${fechaHasta.substr(3,2)}-${fechaHasta.substr(0,2)}`;

    link.attr('href', `/generar_excel/${mostrarDetallado}/${tiendas}/${fechaDesde}/${fechaHasta}`);
    console.log('Tiendas : ',tiendas);
    console.log('Fecha Desde', fechaDesde);
    console.log('Fecha Hasta',fechaHasta);
    console.log('Mostrar Detallado', mostrarDetallado);
}