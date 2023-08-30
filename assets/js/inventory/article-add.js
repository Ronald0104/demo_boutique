App.Producto.EsNuevo = false;
App.Producto.EsEdit = false;
App.Producto = (function() {
    // Métodos privados
    var edit = function() { console.log('Método prívado edit'); }
    function filter() { console.log('Metodo privado filter'); }

    // Métodos públicos
    var fn_InitNuevo = function() {
        $('#estadoArticulo').prop('checked', true);
        $('#articuloId').val('0');
        if(EsNuevo || App.Producto.EsNuevo) $('#categoriaArticulo').val('').trigger('change');
        $('#tiendaArticulo').val(tiendaSel.tiendaId);
        $('#etapaArticulo').val('1');
        var FechaHoy = new Date();
        $('#fechaRegistroArticulo').val(FechaHoy.format());
        // $('#fechaCompraArticulo').val(FechaHoy.format());
        // fn_ObtenerCodigo($('#categoriaArticulo').val(), function(data) { $('#codigoArticulo').val(data.trim()); });
    }
    var fn_Nuevo = () => { fn_LimpiarArticulo(); }
    return {
        InitNuevo : fn_InitNuevo,
        Nuevo : fn_Nuevo 
    }
})();


$(function() {    
    // $('#fechaRegistroArticulo').datepicker({ formatDate: 'dd/mm/yy' });
    
    $('#categoriaArticulo').select2({ minimumResultsForSearch: Infinity, dropdownParent: $('#modal-article-add') });
    $('#tiendaArticulo').select2({ minimumResultsForSearch: Infinity, dropdownParent: $('#modal-article-add') });
    $('#tipoArticulo').select2({ minimumResultsForSearch: Infinity, dropdownParent: $('#modal-article-add') });
    $('#condicionArticulo').select2({ minimumResultsForSearch: Infinity, dropdownParent: $('#modal-article-add') });
    $('#etapaArticulo').select2({ minimumResultsForSearch: Infinity, dropdownParent: $('#modal-article-add') });

    $('#tallaArticuloSelect').select2({ minimumResultsForSearch: Infinity, dropdownParent: $('#modal-article-add') });
    $('#colorArticuloSelect').select2({ minimumResultsForSearch: Infinity, dropdownParent: $('#modal-article-add') });
    $('#disenoArticuloSelect').select2({ minimumResultsForSearch: Infinity, dropdownParent: $('#modal-article-add') });

    /* Funciones para cargar las imagenes */
    let isLoadImages = true;
    $("#fileInput").fileinput({
        // theme: "fas", 
        uploadUrl: "/inventario/upload",
        showUpload: true,
        previewFileType: 'any',
        browseOnZoneClick: false,
        maxFileCount: 3,
        language: 'es',
        showRemove: true,
        // actionDelete: '<button type="button" class="kv-file-remove {removeClass}" title="{removeTitle}"{dataUrl}{dataKey}>{removeIcon}</button>\n'
        allowedFileTypes: ['image'],
        allowedFileExtensions: ["png", "jpg", "jpeg"],
        showCancel: true,
        overwriteInitial: false,
        // initialPreview: [
        //     "http://boutiqueglamour.hrosolutions.pe/assets/img/articles/TSAC0000002/dfgdfgdfgdfgdfg.jpeg",
        //     "http://boutiqueglamour.hrosolutions.pe/assets/img/articles/TSAC0000002/GRACIAS.jpg"
        // ],
        initialPreviewAsData: true,
        // initialPreviewFileType: 'image',
        // initialPreviewConfig: [
        //     {
        //         key: 0,
        //         url: '/image/remove', //custom URL
        //         caption: "dfgdfgdfgdfgdfg.jpeg",
        //         size: 576237, 
        //         zoomData: "http://boutiqueglamour.hrosolutions.pe/assets/img/articles/TSAC0000002/dfgdfgdfgdfgdfg.jpeg",
        //         extra: {
        //             image: '847 Queen Street West, Toronto, ON, Canada_1.JPG'
        //         }
        //       },
        //      {
        //         key: 1,
        //         url: '/image/remove', //custom URL
        //         caption: "dfgdfgdfgdfgdfg.jpeg",
        //         size: 576237, 
        //         zoomData: "http://boutiqueglamour.hrosolutions.pe/assets/img/articles/TSAC0000002/dfgdfgdfgdfgdfg.jpeg",
        //         extra: {
        //             image: '847 Queen Street West, Toronto, ON, Canada_2.JPG'
        //         }
        //       },
        // ],

        //"fileActionSettings":{"showDrag":false}
        // uploadExtraData: {
        //     // "PublicacionId": $("#PublicacionId").val()
        //     "codigoArticulo": function() {
        //         return $('#PRD_codigoArticulo').val();
        //     }
        //     // "codigoArticulo": `'${$('#PRD_codigoArticulo').val()}'`
        // },
        uploadExtraData: function() {
            return {
                codigoArticulo: $('#PRD_codigoArticulo').val()
            }
        }
    })
    .on("fileuploaded", function(event, previewId, index, fileId) {
        console.log('File Uploaded_Edit', 'ID: ' + fileId + ', Thumb ID: ' + previewId);
        isLoadImages = true;          
    }).on('fileuploaderror', function(event, data, msg) {
        console.log('File Upload Error Edit', 'ID: ' + data.fileId + ', Thumb ID: ' + data.previewId, data);
        isLoadImages = false;
    }).on('filebatchuploadcomplete', function(event, preview, config, tags, extraData) {
        console.log('File Batch Uploaded Edit', event, preview, config, tags, extraData);
        // $('#fileInput').fileinput('reset');
        // $('#fileInput').fileinput('destroy');     
        if (isLoadImages) {            
            $('#fileInput').fileinput('refresh');   
            // Recargar las fotos 
            fn_ObtenerArticuloImagenes($('#PRD_codigoArticulo').val(), function(data) {
                fn_MostrarArticuloImagenes(data);
            });
        }
    });


    $('#categoriaArticulo').on('change', function() {
        var categoriaId = $(this).val();  
        if (App.Producto.EsEdit) return;
        // console.log('no es edit');
        // console.log("se limpio el codigo");
        $('#PRD_codigoArticulo').val('');
        if (!categoriaId) return;
        fn_ObtenerCodigo(categoriaId, function(data) {
            $('#PRD_codigoArticulo').val(data.trim());
        })
    })
    $('#PRD_codigoArticulo').on('keypress', function(evt) {
        var keyCode = (evt.which) ? evt.which : evt.keyCode;
        if (keyCode == 13) {
            var articuloId = $('#articuloId').val();
            if (articuloId == "0") {
                var articuloCode = $(this).val();
                if (articuloCode.length >= 4) {
                    articuloCode = fn_CompletarCodigo($(this).val());
                    $(this).val(articuloCode);
                    fn_ObtenerArticulo(articuloCode, function(data) {
                        if (data) {
                            fn_MostrarArticulo(data);
                        }
                    })
                }
            }
        } else {
            $input = $(this);
            setTimeout(function() {
                $input.val($input.val().toUpperCase());
            }, 50);
        }
    })
    $('#PRD_codigoArticulo').on('blur', function(evt) {
        var articuloId = $('#articuloId').val();
        if (articuloId == "0") {
            var articuloCode = $(this).val();
            if (articuloCode.length >= 4) {
                articuloCode = fn_CompletarCodigo($(this).val());
                $(this).val(articuloCode);
                fn_ObtenerArticulo(articuloCode, function(data) {
                    if (data) {
                        fn_MostrarArticulo(data);
                    }
                })
            }
        }
    })
    $('#fechaCompraArticulo').datepicker({ formatDate: 'dd/mm/yy' });
    $('#btn-article-add').on('click', function() {
        var isValid = $('#form-article-add').valid();
        if (!isValid) { $('#PRD_codigoArticulo').focus(); return;}
        var articulo = new FormData($('#form-article-add').get(0));
        var articuloForm = $('#form-article-add').serialize();
        var articuloJson = $('#form-article-add').serializeFormJSON();        
        articuloJson.estadoArticulo = (articuloJson.estadoArticulo == undefined) ? 0 : 1;
        if(articuloJson.fechaCompraArticulo){
            articuloJson.fechaCompraArticulo = getDate(articuloJson.fechaCompraArticulo);
            articuloJson.fechaCompraArticulo = articuloJson.fechaCompraArticulo.toISOString();
        }        
        // console.log(articuloJson);
        // return;
        fn_RegistrarArticulo(articuloJson, function(data, jqXHR) {
            // console.log(data);
            var data = JSON.parse(data);  
            if (data.code == 0) {
                mostrarError($('#form-article-add-error'), data.message);
            } else {    
                tbl_articulos.ajax.reload(null, false);  
                var action = jqXHR.getResponseHeader('Content-Type-Action');
                fn_LimpiarArticulo($('#form-article-add'));
                if (action == "Insert")
                    swal('', 'Artículo registrado correctamente', 'info');
                else
                    swal('', 'Artículo actualizado correctamente', 'info');
                $('#modal-article-add').modal('hide');
                // $('#foto_preview').attr('src', '/assets/img/default_256.png');
                // $('#form-article-add').find(".form-group .col-sm-8").removeClass('has-success');                
                // listarArticulos();
            }
        })
    });
    $('#btn-article-new').on('click', function() {
        // console.log('nuevo');
        EsNuevo = true;
        App.Producto.EsNuevo = true;
        App.Producto.EsEdit= false;
        fn_Nuevo();
        EsNuevo = false;
    });

    $(document).on('click', '.link-cliente', function (evt) {
        evt.preventDefault();
        let clienteId = $(this).data('cliente');
        let nroDocumento = $(this).data('cliente-nro');
        fn_MostrarCliente(clienteId, nroDocumento);
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

    $(document).on('click','.btn-delete-foto', function(evt) {
        evt.preventDefault();
        let data = $(this).data();
        let id = data.id;
        let path = data.path;
        let $this = this;
        fn_EliminarArticuloImagen(id, path, function(data) {
            console.log(data);
            $($this).parent().parent().parent().remove();
        })
    });

    fn_InitNuevo();
})

var fn_InitNuevo = function() {
    $('#estadoArticulo').prop('checked', true);
    $('#articuloId').val('0');
    if(EsNuevo) $('#categoriaArticulo').val('').trigger('change');
    $('#tiendaArticulo').val(tiendaSel.tiendaId);
    $('#etapaArticulo').val('1');
    var FechaHoy = new Date();
    $('#fechaRegistroArticulo').val(FechaHoy.format());

    $("#content-fotos").html("");
    $("#content-fileinput").addClass("d-none");
    // $('#fechaCompraArticulo').val(FechaHoy.format());
    // fn_ObtenerCodigo($('#categoriaArticulo').val(), function(data) {
    //  $('#codigoArticulo').val(data.trim());
    // });
}
var fn_Nuevo = () => {
    fn_LimpiarArticulo();
}
var fn_LimpiarArticulo = () => {
    valArticulo.resetForm();
    $('#form-article-add')[0].reset();
    fn_InitNuevo();
}
function fn_LimpiarArticuloFull() {
    valArticulo.resetForm();
    $('#form-article-add')[0].reset();
    $("#content-fotos").html("");
    $('#fileInput').fileinput('refresh');
    //console.log("Fin limpiar full");
}
function fn_CompletarCodigo(codigo) {
    let prefijo = "";
    let correlativo;
    let newCodigo;
    for (i = 0; i <= codigo.length; i++) {
        if (isNaN(codigo[i]))
            prefijo += codigo[i];
        else
            break;
    }
    correlativo = Number(codigo.substr(prefijo.length, codigo.length - prefijo.length));
    newCodigo = prefijo + zfill(correlativo, 11 - prefijo.length);
    return newCodigo;
}
function fn_ObtenerCodigo(categoriaId, Success) {
    var request = $.ajax({
        method: 'POST',
        url: '/inventario/obtenerCorrelativo',
        data: { categoriaId: categoriaId },
        beforeSend: Call_Progress(true)
    })
        .done(function(data, textStatus, jqXHR) {
            if (Success != undefined && typeof Success == "function")
                Success(data);
        })
        .fail(function(jqXHR, textStatus) {
            console.log("Error : " + textStatus);
        })
        .always(function() {
            console.log('completado');
            Call_Progress(false);
        });
}
var fn_ObtenerArticuloById = function(articuloId, Success) {
    $.ajax({
            method: 'POST',
            url: '/inventario/articuloById',
            data: { articuloId: articuloId },
            dataType: 'json',
            beforeSend: Call_Progress(true)
        })
        .done(function(data) {
            if (Success != undefined && typeof Success == "function") Success(data);
        })
        .fail(function(jqXHR) {
            console.log('error');
            console.log(jqXHR.responseText);
        })
        .always(function() {
            Call_Progress(false)
        })
}
var fn_ObtenerArticulo = function(codigoArticulo, Success) {
    $.ajax({
            method: 'POST',
            url: '/inventario/articuloByCode',
            data: { articuloCode: codigoArticulo },
            dataType: 'json',
            beforeSend: Call_Progress(true)
        })
        .done(function(data) {
            if (Success != undefined && typeof Success == "function") Success(data);
        })
        .fail(function(jqXHR) {
            console.log('error');
            console.log(jqXHR.responseText);
        })
        .always(function() {
            Call_Progress(false)
        })
}
var fn_ObtenerArticuloImagenes = function(codigoArticulo, Success) {
    $.ajax({
            method: 'POST',
            url: '/inventario/articuloImagenes',
            data: { articuloCode: codigoArticulo },
            dataType: 'json',
            beforeSend: Call_Progress(true)
        })
        .done(function(data) {
            if (Success != undefined && typeof Success == "function") Success(data);
        })
        .fail(function(jqXHR) {
            console.log('error');
            console.log(jqXHR.responseText);
        })
        .always(function() {
            Call_Progress(false)
        })
}
var fn_MostrarArticulo = function(data) {  
    // console.log(data); 
    // var articulo = data[0];
    articulo = data.articulo; 
    historial = data.historial;  
    imagenes = data.imagenes;  
    // console.log(articulo);  
    articulo.fechaCreacion = reviver(0, articulo.fechaCreacion);
    articulo.fechaCompra = reviver(0, articulo.fechaCompra);

    $('#estadoArticulo').prop('checked', Number(articulo.activo));
    App.Producto.EsEdit = true;
    $('#categoriaArticulo').val(articulo.categoriaId).trigger('change');
    fn_ListarTallasxCategoria(articulo.categoriaId, function() {
        $('#tallaArticuloSelect').val(articulo.tallaId).trigger('change');
    });
    fn_ListarColoresxCategoria(articulo.categoriaId, function() {
        $('#colorArticuloSelect').val(articulo.colorId).trigger('change');
    });
    fn_ListarDisenosxCategoria(articulo.categoriaId, function() {
        $('#disenoArticuloSelect').val(articulo.disenoId).trigger('change');
    });
    // App.EsEdit = false;

    $('#articuloId').val(articulo.articuloId);    
    $('#PRD_codigoArticulo').val(articulo.codigo);    
    $('#nombreArticulo').val(articulo.nombre);
    $('#tiendaArticulo').val(articulo.tiendaId).trigger('change');
    $('#etapaArticulo').val(articulo.estadoId).trigger('change');
    $('#marcaArticulo').val(articulo.marca);
    $('#tallaArticulo').val(articulo.talla);
    $('#colorArticulo').val(articulo.color);
    $('#telaArticulo').val(articulo.tela);
    $('#disenoArticulo').val(articulo.diseno);
    $('#tipoArticulo').val(articulo.tipoPrenda).trigger('change');
    $('#caracteristicasArticulo').val(articulo.caracteristicas);    
    $('#condicionArticulo').val(articulo.condicion).trigger('change');
    $('#fechaRegistroArticulo').val((articulo.fechaCreacion == null) ? '' : articulo.fechaCreacion.format());
    $('#fechaCompraArticulo').val((articulo.fechaCompra == null || !isValidDate(articulo.fechaCompra)) ? '' : articulo.fechaCompra.format());
    $('#precioCompraArticulo').val(Number(articulo.precioCompra).toFixed(2));
    $('#precioAlquilerArticulo').val(Number(articulo.precioAlquiler).toFixed(2));
    $('#precioVentaArticulo').val(Number(articulo.precioVenta).toFixed(2));

    // Historial de artículo
    $('#tb_article_history').html('');
    $('#total-articulo').find('b').text('0');
    if (historial.length > 0){
        var tr;
        var total = 0;
        historial.forEach(function(e, i) {
            tr = $('<tr>').attr('tabindex', 0);
            tr.append("<td><a href='#' class='link-venta' data-venta='"+e.ventaId+"'>"+e.ventaCode+"</a></td>");
            tr.append("<td>"+e.tipoOperacion+"</td>");
            tr.append("<td>"+e.estado+"</td>");
            tr.append("<td><a href='#' class='link-cliente' data-cliente='"+e.clienteId+"' data-cliente-nro='"+e.nroDocumento+"'>"+e.nroDocumento+"</a></td>");
            tr.append("<td>"+e.cliente+"</td>");
            tr.append("<td>"+e.fechaAlquiler+"</td>");
            tr.append("<td>"+e.importeTotal+"</td>");
            $('#tb_article_history').append(tr);

            total += parseFloat(e.importeTotal);
        })
        $('#total-articulo').find('b').text(total); 
    }
    $("#content-fileinput").removeClass("d-none");
    if(imagenes.length > 0) {
        imagenes.forEach((e, i) => {
            let foto = `
            <div class="col-sm-6 col-item-foto">
                <div class="row">
                    <a href="${e.path}" target="_blank">
                        <img src="${e.path}" class="foto">   
                        <button class="btn btn-warning btn-delete-foto" data-id="${e.articuloImagenId}" data-path="${e.path}"><i class="icon icon-trash"></i></button>                                                                 
                    </a>
                </div>   
            </div> 
            `;
            $("#content-fotos").append(foto);
        })
    }
}
var fn_MostrarArticuloImagenes = function(imagenes) {
    $("#content-fotos").html("");
    if(imagenes.length > 0) {
        imagenes.forEach((e, i) => {
            let foto = `
            <div class="col-sm-6 col-item-foto">
                <div class="row">
                    <a href="${e.path}" target="_blank">
                        <img src="${e.path}" class="foto">                                                                    
                        <button class="btn btn-warning btn-delete-foto" data-id="${e.articuloImagenId}" data-path="${e.path}"><i class="icon icon-trash"></i></button>
                    </a>
                </div>   
            </div> 
            `;
            $("#content-fotos").append(foto);
        })
    }
}
var fn_EliminarArticuloImagen = function(id, path, Success) {
    $.ajax({
        method: 'POST',
        url: '/inventario/articuloImagenEliminar',
        data: { articuloImagenId: id, targetImagen: path},
        dataType: 'json',
        beforeSend: Call_Progress(true)
    })
    .done(function(data) {
        if (Success != undefined && typeof Success == "function") Success(data);
    })
    .fail(function(jqXHR) {
        console.log('error');
        console.log(jqXHR.responseText);
    })
    .always(function() {
        Call_Progress(false)
    })
}

var fn_RegistrarArticulo = function(articulo, Success) {    
    // var request = 
    $.ajax({
        method: 'POST',
        url: '/inventario/articuloAgregarAjax',
        data: articulo,
        beforeSend: Call_Progress(true)
    })
    .done(function(data, textStatus, jqXHR) {            
        if (Success != undefined && typeof Success == "function") Success(data, jqXHR);
    })
    .fail(function(jqXHR, textStatus) {
        console.log("Error : " + textStatus);
        console.log(jqXHR.responseText);
    })
    .always(function() {
        Call_Progress(false);
    });
}

function fn_MostrarCliente(clienteId, nroDocumento) {
    fn_ObtenerModalRegistrarCliente(function () {
        fn_LimpiarCliente();
        $('#customerId_Add').val(clienteId);
        $('#nroDocumento_Add').val(nroDocumento);
        $('#nroDocumento_Add').data('valueOld', nroDocumento);
        fn_ObtenerCliente(() => $('#modal-register-customer').modal('show'));
    })
}

function fn_ListarTallasxCategoria(categoriaId, Success) {
    $.ajax({
        method: 'POST',
        url: '/inventario/listarTallasxCategoria',
        data: { categoriaId : categoriaId}
    })
    .done(function(data) {
        $('#tallaArticuloSelect').html('');
        let tallas = JSON.parse(data); 
        $('#tallaArticuloSelect').select2("destroy");      
        $('#tallaArticuloSelect').append("<option value='0'>.. SELECCIONE ..</option>");                     
        if (tallas.length>0) tallas.forEach((e) => $('#tallaArticuloSelect').append("<option value='"+e.id+"'>"+e.nombre+"</option>")); 
        $('#tallaArticuloSelect').select2({ dropdownParent: $('#modal-article-add') }); 
        if (Success != undefined && typeof Success == "function") Success();
    })
    .fail(function(jqXHR){
        console.log(jqXHR.responseText);
    })
    .always(function(){
        // Call_Progress(false);
    })
}

function fn_ListarColoresxCategoria(categoriaId, Success) {
    $.ajax({
        method: 'POST',
        url: '/inventario/listarColoresxCategoria',
        data: { categoriaId : categoriaId},
        beforeSend: Call_Progress(true)
    })
    .done(function(data) {
        $('#colorArticuloSelect').html('');
        let colores = JSON.parse(data);
        $('#colorArticuloSelect').select2("destroy");   
        $('#colorArticuloSelect').append("<option value='0'>.. SELECCIONE ..</option>");    
        if (colores.length>0) colores.forEach((e) => $('#colorArticuloSelect').append("<option value='"+e.id+"'>"+e.nombre+"</option>"));
        $('#colorArticuloSelect').select2({ dropdownParent: $('#modal-article-add') });
        if (Success != undefined && typeof Success == "function") Success();
    })
    .fail(function(jqXHR){
        console.log(jqXHR.responseText);
    })
    .always(function(){
        Call_Progress(false)
    })
}

function fn_ListarDisenosxCategoria(categoriaId, Success) {
    $.ajax({
        method: 'POST',
        url: '/inventario/listarDisenosxCategoria',
        data: { categoriaId : categoriaId},
        beforeSend: Call_Progress(true)
    })
    .done(function(data) {
        $('#disenoArticuloSelect').html('');
        let disenos = JSON.parse(data);  
        $('#disenoArticuloSelect').select2("destroy");
        $('#disenoArticuloSelect').append("<option value='0'>TODOS</option>");         
        if (disenos.length > 0) disenos.forEach((e) => $('#disenoArticuloSelect').append("<option value='"+e.id+"'>"+e.nombre+"</option>"));
        $('#disenoArticuloSelect').select2();
        if (Success != undefined && typeof Success == "function") Success();
    })
    .fail(function(jqXHR){
        console.log(jqXHR.responseText);
    })
    .always(function(){
        Call_Progress(false)
    })
}

// reglas formulario
var valArticulo = $('#form-article-add').validate({
    rules: {
        categoriaArticulo: "required",
        PRD_codigoArticulo: "required",
        nombreArticulo: "required",
        tiendaArticulo: "required",
        etapaArticulo: "required"
    },
    messages: {
        categoriaArticulo: "Por favor, seleccione una categoria",
        PRD_codigoArticulo: "Por favor, ingrese un código al artículo",
        nombreArticulo: "Por favor, ingrese un nombre al artículo",
        tiendaArticulo: "Por favor, seleccione una tienda",
        etapaArticulo: "Por favor, seleccione el estado",
    },
    highlight: function(element, errorClass, validClass) {
        $(element).parents(".col-sm-8").addClass("has-error").removeClass("has-success");
        $(element).parents(".col-sm-4").addClass("has-error").removeClass("has-success");
    },
    unhighlight: function(element, errorClass, validClass) {
        $(element).parents(".col-sm-8").addClass("has-success").removeClass("has-error");
        $(element).parents(".col-sm-4").addClass("has-error").removeClass("has-success");
    }
});