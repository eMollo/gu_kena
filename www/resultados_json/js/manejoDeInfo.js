var grafico = null;
// var tipo = 'pie';// torta
var titulo;
var mostrar = false;// carteles fijos

$(function () {
    json = categoria + "_" + ua + "_" + claustro + '.json';
    llamadaAjax();
    //setInterval(llamadaAjax, 60000);

});

function solo_FACE() {
    if (ua != 'FACE') {
        ua = 'FACE';
        var selText = $('#FACE').children("h7").html();
        $('#FACE').parent('li').siblings().removeClass('active');
        $('#FACE').parents('.nav-item').find('.selection').html(selText);
        $('#FACE').parents('li').addClass("active");
    }

}
function cambioTablaGrafico(opcion, valor) {
    if (opcion == 'ua') {
        ua = valor;
    } else if (opcion == 'claustro') {
        claustro = valor;
    } else if (opcion == 'categoria') {
        categoria = valor;
    } else
        console.log('Error variable inexistente');
    json = categoria + "_" + ua + "_" + claustro + '.json';
    // cambioGrafico();
    llamadaAjax();
}
function llamadaAjax() {
    $('#loader').show();
    $('#refresh').hide();
    $.ajax({
        url: carpetajson + '/' + json,
        dataType: 'json',
        cache: false,
        complete: function (data) {
            $('#loader').hide();
            $('#refresh').show();
        },
        success: function (data) {
            $('#alerta').hide();
            $('#pie').removeClass('fixed-bottom');
            $('#pie').addClass('position-relative');
            actualizarTitulo(data);
            actualizarTabla(data);
            actualizarGrafico(data);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            borrar();
        }
    });
    setInterval("llamadaAjax()", 600000);
}
function borrar() {
    $('#contGrafico').hide();
    $('#contTabla').hide();
    $('#carteles').hide();
    $('#alerta').show();
    /*$('#pie').removeClass('position-relative');
    $('#pie').addClass('fixed-bottom');*/
}
function actualizarTabla(data) {
    $('#contTabla').show();
    $('#tabla').bootstrapTable('destroy');
    $('#tabla').bootstrapTable({
        data: data.data,
        columns: data.columns
    });
    $('#tabla2').bootstrapTable('destroy');
    document.getElementById('titulo2').innerHTML = "";
    if (data.hasOwnProperty('data2')) {
        document.getElementById('titulo2').innerHTML = data.titulo2;
        $('#tabla2').bootstrapTable('destroy');
        $('#tabla2').bootstrapTable({
            data: data.data2,
            columns: data.columns2
        });
    }

}
function actualizarGrafico(data) {
    // var arregloTotal = carga(data);
    $('#contGrafico').show();
    if (grafico != null) {
        grafico.destroy();
    }
    if (data.hasOwnProperty('titulo_grafico'))
    {
        tituloGrafico = data.titulo_grafico;
        mostrarGrafico = true;
    } else
    {
        tituloGrafico = "";
        mostrarGrafico = false;
    }
    grafico = new Chart(document.getElementById("grafico"), {
        type: 'horizontalBar', // 'pie','horizontalBar',tipo
        data: {
            labels: data.labels,
            datasets: [{
                    backgroundColor: "#3498db", // arregloTotal[0],
                    borderColor: "#1a5276", // arregloTotal[1],
                    borderWidth: 2, // arregloTotal[2],
                    data: data.total,
                }]
        },
        options: {
            responsive: true, //carteleria fija
            showAllTooltips: mostrar, //carteleria fija
            legend: {
                display: false, // arregloTotal[3],
                position: "top", // arregloTotal[4],
            },
            title: {
                display: mostrarGrafico,
                text: tituloGrafico
            },
            scales: {
                xAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
            },
            tooltips: {
                callbacks: {
                    enabled: true,
                    mode: 'single',
                    label: function (tooltipItems, data) {
                        return "";
                    }
                }
            }
        },
    });
}
/*
 * para mostrar carteles
 
 Chart.pluginService.register({
 beforeRender: function(chart) {
 if (chart.config.options.showAllTooltips) {
 // create an array of tooltips
 // we can't use the chart tooltip because there is only one tooltip per chart
 chart.pluginTooltips = [];
 chart.config.data.datasets.forEach(function(dataset, i) {
 chart.getDatasetMeta(i).data.forEach(function(sector, j) {
 chart.pluginTooltips.push(new Chart.Tooltip({
 _chart: chart.chart,
 _chartInstance: chart,
 _data: chart.data,
 _options: chart.options.tooltips,
 _active: [sector]
 }, chart));
 });
 });
 
 // turn off normal tooltips
 chart.options.tooltips.enabled = false;
 }
 },
 afterDraw: function(chart, easing) {
 if (chart.config.options.showAllTooltips) {
 // we don't want the permanent tooltips to animate, so don't do anything till the animation runs atleast once
 if (!chart.allTooltipsOnce) {
 if (easing !== 1)
 return;
 chart.allTooltipsOnce = true;
 }
 
 // turn on tooltips
 chart.options.tooltips.enabled = true;
 Chart.helpers.each(chart.pluginTooltips, function(tooltip) {
 tooltip.initialize();
 tooltip.update();
 // we don't actually need this since we are not animating tooltips
 tooltip.pivot();
 tooltip.transition(easing).draw();
 });
 chart.options.tooltips.enabled = false;
 }
 }
 });*/

/*
 * en el caso de la torta function carga(data){ var i, salida=[], color=[],
 * aux=arregloDeColore(), borderTam=[], borderColor=[]; var display =
 * (tipo=='pie')?true:false; var lado = (tipo=='pie')?"right":"top"; for (i in
 * data.data){ borderTam[i]=2; borderColor[i]=(tipo=='pie')?'black':"#1a5276";
 * color[i]= (tipo=='pie')?aux[i]:"#3498db"; } salida.push(color);//se
 * encuentran los colores que representan la lista [0]
 * salida.push(borderColor);//se encuentra el color del borde de cada porcion en
 * este caso black [1] salida.push(borderTam);//se encuentra el tamaño del borde
 * de cada porcion en este caso es 2 [2] salida.push(display);//para la variable
 * display true para pie falso horizBar [3] salida.push(lado); //para la
 * posicion[4] return salida; } function arregloDeColore(){ var salida =
 * ["#5793F3","#DD4444","#FD9C35","#D4Df5A","#FEC42C","#5578C2","#DD4D79","#BD3B47"];
 * return salida; }
 */
// selector
$(".dropdown-menu").on('click', 'li a', function () {
    var selText = $(this).children("h7").html();
    $(this).parent('li').siblings().removeClass('active');
    $(this).parents('.nav-item').find('.selection').html(selText);
    $(this).parents('li').addClass("active");
});

function actualizarTitulo(data) {
    $('#carteles').show();
    document.getElementById('titulo').innerHTML = data.titulo;
    document.getElementById('enviadoConfirmado').innerHTML = "Mesas Cargadas: "
            + data.enviadas + '</br>' + "Mesas Confirmadas: "
            + data.confirmadas;
    document.getElementById('hora').innerHTML = "Actualizado a: " + data.fecha;
    document.getElementById('tituloTabla').innerHTML = data.titulo;
    titulo = data.titulo;
}
/*
 * cambio de torta o barras function cambioGrafico(){ if (ua == 'TODO' &&
 * claustro == 'T' && categoria == 'R') { tipo = 'pie'; } else{ tipo =
 * 'horizontalBar';} }
 */
function exportarTabla() {
    $("#tabla").table2excel({
        name: titulo,
        filename: titulo
    });
}
$(document).ready(function () {
    $(".btn-outline-primary:first").click(function () {
        $(this).button('toggle');
    });
});
$('#mostrarCartel').on('click', function () {
    mostrar = mostrar == true ? false : true;
    llamadaAjax();
});

 