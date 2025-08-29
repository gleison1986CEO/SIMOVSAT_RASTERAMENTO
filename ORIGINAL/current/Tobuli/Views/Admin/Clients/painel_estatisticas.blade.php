<script type="text/javascript" src="http://ajax.googleapis.com/ajax/
libs/jquery/1.3.0/jquery.min.js"></script>
<?php

////////////// INCLUDES SERVIDOR GRAFICO /////////////////////////////////////////
////////////// INCLUDES SERVIDOR GRAFICO /////////////////////////////////////////
////////////// INCLUDES SERVIDOR GRAFICO /////////////////////////////////////////
////////////// INCLUDES SERVIDOR GRAFICO /////////////////////////////////////////

$Total_veiculos = file_get_contents('http://atise.website/api/alertas/graficos/SGA/Total_veiculos.php');
$Ativos_sga = file_get_contents('http://atise.website/api/alertas/graficos/SGA/Ativos_sga.php');
$Inativos_sga = file_get_contents('http://atise.website/api/alertas/graficos/SGA/Inativos_sga.php');
$Nao_importados = file_get_contents('http://atise.website/api/alertas/graficos/SGA/Nao_importados.php');
$Importados_sga = file_get_contents('http://atise.website/api/alertas/graficos/SGA/Importados_sga.php');
$Quantidade_veiculos = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/aparelhos_.php');
$Inativos_bloqueados = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/inativados.php');
$Rastreadores_desligados = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/rastreador_desligado.php');

//manutenção preventiva

$preventiva_6h =  file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/preventiva_6h.php');
$preventiva_12h = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/preventiva_12h.php');
$preventiva_24h = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/preventiva_24h.php');
$preventiva_72h = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/preventiva_72h.php');
$preventiva_5dias = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/preventiva_5dias.php');


//Alerta de Conexão

$CONECTADO = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/conectados.php');
$UMA_6 = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/entre1_6horas.php');
$SETE_12 = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/entre7_12horas.php');
$VINTEQUATRO_48 = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/entre24_48horas.php');
$SETENTA_DUAS = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/entre72horas.php');


//SEM CONEXAO

$SEM_CONEXAO_HOJE = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/um_dia_conexao.php');
$SEM_CONEXAO_48 = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/dois_dias_conexao.php');


//EM MOVIMENTO / PARADO
$IGNICAO_OFF = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/ignicao_off.php');
$MOVIMENTO_ON = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/em_movimento.php');
////////////GRAFICOS ////////////////////////////////////////////////////////////////////////////////////

//IGNICAO

$IGNICAO_OFF_24 = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/iginicao_24.php');
$IGNICAO_OFF_48 = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/ignicao_48.php');
$IGNICAO_OFF_72 = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/ignicao_72.php');
$RASTREADORES = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/aparelhos_.php');
$DESLIGADOS = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/rastreador_desligado.php');

//Alerta Anti-Fraude

$ALTA = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/bateria_alta.php');
$BAIXA = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/bateria_baixa.php');
$CARREGAMENTO = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/carregamento.php');
$DESCONECTADO = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/rastreador_desligado.php');
$BATERIA_48HORAS_SEM = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/dois_dias_conexao.php');
$BATERIA_72HORAS_SEM = file_get_contents('http://atise.website/api/alertas/graficos/alertas_conexao/tres_dias_conexao.php');
$RISCO = file_get_contents('http://atise.website/api/alertas/graficos/alertas_fraudes/area_risco.php');


//
// $dois_dias = file_get_contents('');
$usuários_simov = file_get_contents('http://atise.website/api/alertas/graficos/dados_usuarios/usuarios.php');
$ativos_sga = file_get_contents('http://atise.website/api/alertas/graficos/SGA/Ativos_sga.php');
$rastreadores_ = file_get_contents('http://atise.website/api/alertas/graficos/dados_usuarios/rastreadores.php');
$veiculos_ = file_get_contents('http://atise.website/api/alertas/graficos/dados_usuarios/veiculos.php');

////////////GRAFICOS ////////////////////////////////////////////////////////////////////////////////////


//SGA
$inativosSGA_   = file_get_contents('http://atise.website/api/alertas/graficos/SGA/Inativos_sga.php');
$nao_importados = file_get_contents('http://atise.website/api/alertas/graficos/SGA/Nao_importados.php');

//ALERTAS VEÍCULOS

$conectados   = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/conectados.php');
$on_line   = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/online.php');
$off_line   = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/offline.php');
$ocioso   = file_get_contents('http://atise.website/api/alertas/graficos/SGA/Inativos_sga.php');
$desligados  = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/rastreador_desligado.php');
$parado   = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/parado.php');
$em_movimento   = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/em_movimento.php');
$ignição_on   = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/ignicao_on.php');
$ignição_off   = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/ignicao_off.php');
$risco   = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/area_risco.php');
$acesso_suspenso   = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/inativados.php');


// alerta de conexão
$treze_24horas =  file_get_contents('http://atise.website/api/alertas/graficos/bateria_deconectado/13_24.php');
$cinco_horas =  file_get_contents('http://atise.website/api/alertas/graficos/bateria_deconectado/cinco_hora.php');
$conectados_24horas =  file_get_contents('http://atise.website/api/alertas/graficos/bateria_deconectado/conectados.php');
$duas_horas =  file_get_contents('http://atise.website/api/alertas/graficos/bateria_deconectado/duas_hora.php');
$quatro_horas =  file_get_contents('http://atise.website/api/alertas/graficos/bateria_deconectado/quatro_horas.php');
$seis_horas =  file_get_contents('http://atise.website/api/alertas/graficos/bateria_deconectado/seis_doze.php');
$tres_horas =  file_get_contents('http://atise.website/api/alertas/graficos/bateria_deconectado/tres_hora.php');
$uma_horas =  file_get_contents('http://atise.website/api/alertas/graficos/bateria_deconectado/uma_hora.php');
$hoje_uma_hora_ =  file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/conectados1hora.php');

$online_menos1 = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/online1hora.php');
$online_menos1dia = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/online1dia.php');
$online_mais1dia = file_get_contents('http://atise.website/api/alertas/graficos/alertas_veiculos/onlinemais1dia.php');
////////////// INCLUDES SERVIDOR GRAFICO /////////////////////////////////////////
////////////// INCLUDES SERVIDOR GRAFICO /////////////////////////////////////////
////////////// INCLUDES SERVIDOR GRAFICO /////////////////////////////////////////
////////////// INCLUDES SERVIDOR GRAFICO /////////////////////////////////////////

?>

<!-- GRAFICOS -->
<!-- GRAFICOS -->
<!-- GRAFICOS -->
<!-- GRAFICOS -->



<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['total', 'total em dados simovsat fraudes'],
          ['on-line',                                    <?php echo $on_line?>],
          ['- 1 hora',                                   <?php echo $online_menos1 ?>],
          ['- 1 dia',                                    <?php echo $online_menos1dia?>],
          ['+ 1 dia',                                    <?php echo $online_mais1dia?>],
          ['nunca-conect.',                              <?php echo $Inativos_bloqueados?>],

        ]);

        var options = {
          pieHole: 0.6,
          legend: {position: 'none'},
          backgroundColor: 'transparent',
          colors: ['#33cc33', '#00c28e', '#ff914c', '#fede59', '#d00f0f', '#009dbe',"#3377ff"]
          
        };

        var chart = new google.visualization.PieChart(document.getElementById('alertas_conexao'));
        chart.draw(data, options);
      }
</script>


<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['total', 'total alertas de veiculos'],
          ['ignição on',                      <?php echo $ignição_on ?>],
          ['on-line' ,                        <?php echo $on_line?>],
          ['movimento',                       <?php echo $em_movimento?>],
          ['parado',                          <?php echo $parado?>],
          ['off-line',                        <?php echo $off_line ?>],
          ['ignição off',                     <?php echo $ignição_off ?>],
          ['área de risco',                   <?php echo $risco ?>],
          ['suspenso',                        <?php echo $acesso_suspenso ?>]
        

        ]);
  
        var options = {
          pieHole: 0.6,
          legend: {position: 'none'},
          backgroundColor: 'transparent',
          colors: ['#00b300', '#006600', '#ace600', '#ff9933', '#ff1a1a', '#b30000','#660000',"#1a0000"]
          
        };

        var chart = new google.visualization.PieChart(document.getElementById('alertas_veiculos'));
        chart.draw(data, options);
      }
</script>



<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['total', 'total em manutenção preventiva'],
          ['6h',                             <?php echo $preventiva_6h ?>],
          ['12h',                            <?php echo $preventiva_12h?>],
          ['24h',                            <?php echo $preventiva_24h ?>],
          ['72h',                            <?php echo $preventiva_72h ?>],
          ['+5dias',                         <?php echo $preventiva_5dias ?>],
        ]);
        var options = {
          pieHole: 0.6,
          legend: {position: 'none'},
          backgroundColor: 'transparent',
          colors: ['#ffcc00', '#ff6600', '#ff3300',  '#991f00',"#800000"]
          
        };

        var chart = new google.visualization.PieChart(document.getElementById('manutencao_preventiva'));
        chart.draw(data, options);
      }
</script>


<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['total', 'total em dados simovsat fraudes'],
          ['desconectada',                            <?php echo $DESCONECTADO?>],
          ['off 48h',                                 <?php echo $BATERIA_48HORAS_SEM?>],
          ['off 72h',                                 <?php echo $BATERIA_72HORAS_SEM?>],
          ['risco 24h',                               <?php echo $RISCO?>],
        ]);
        var options = {
          pieHole: 0.6,
          legend: {position: 'none'},
          backgroundColor: 'transparent',
          colors: ['#ffcc00', '#ff6600', '#ff3300',  '#991f00',"#800000"]
          
        };

        var chart = new google.visualization.PieChart(document.getElementById('alertas_antifraude'));
        chart.draw(data, options);
      }
</script>










<!-- GRAFICOS -->
<!-- GRAFICOS -->
<!-- GRAFICOS -->
<!-- GRAFICOS -->







<!-- GRAFICOS -->
<!-- GRAFICOS -->
<!-- GRAFICOS -->
<!-- GRAFICOS -->



<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['total', 'total em dados simovsat'],
          ['desligados',                     <?php echo $Rastreadores_desligados ?>],
          ['bloqueados',                     <?php echo $Inativos_bloqueados ?>]
        ]);

        var options = {
          pieHole: 0.6,
          legend: {position: 'none'},
          backgroundColor: 'transparent',
          colors: ['#33cc33', '#00c28e', '#ff914c', '#fede59', '#d00f0f', '#009dbe',"#3377ff"]
        };

        var chart = new google.visualization.PieChart(document.getElementById('on_off'));
        chart.draw(data, options);
      }
</script>

<script type="text/javascript">


    google.charts.load("current", {packages:['corechart']});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() {
      var data = google.visualization.arrayToDataTable([
        ["Element", "geral", { role: "style" } ],
        ["total ",                 <?php echo $RASTREADORES?>, "#009dbf"],
        ["off 24h",                <?php echo $IGNICAO_OFF_24?>, "#00c28e"],
        ["off 48h",                <?php echo $IGNICAO_OFF_48?>, "#0070c2"],
        ["off 72h",                <?php echo $IGNICAO_OFF_72?>, "#18e23b"],
        ["desligados",             <?php echo $DESLIGADOS?>, "#fede59"]

      ]);

      var view = new google.visualization.DataView(data);
      view.setColumns([0, 1,
                       { calc: "stringify",
                         sourceColumn: 1,
                         type: "string",
                         role: "annotation" },
                       2]);

      var options = {
        legend: {position: 'none'},
        bar: {groupWidth: "95%"},
        backgroundColor: 'transparent',
        legend: { position: "none" },
      };
      var chart = new google.visualization.ColumnChart(document.getElementById("usuarios"));
      chart.draw(view, options);
  }

 
</script>

<script type="text/javascript">
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['total', 'total em dados simovsat'],
          
          ["1h",                        <?php echo $uma_horas?>],
          ["2h",                        <?php echo $duas_horas?>],
          ["3h",                        <?php echo $tres_horas?>],
          ["4h",                        <?php echo $quatro_horas?>],
          ["5h",                        <?php echo $cinco_horas?>],
          ["6h",                        <?php echo $seis_horas?>],
          ["24h",                       <?php echo $conectados_24horas?>],
          ["13h:24h",                   <?php echo $treze_24horas?>]
        ]);

        var options = {
          pieHole: 0.6,
          legend: {position: 'none'},
          backgroundColor: 'transparent',
          colors: ['#e6e600','#ffcc00','#ff6600', '#ff3300', '#e60000', '#b30000','#b30000', '#4d0000']
        };

        var chart = new google.visualization.PieChart(document.getElementById('alerta_conexao_bateria'));
        chart.draw(data, options);
      }
</script>



<script> 
$(document).ready(function(){
setInterval(function(){
      $("#card_")
}, 2800);
});
</script>
<script> 
$(document).ready(function(){
setInterval(function(){
      $("#card_grafico")
}, 2600);
});
</script>
<script> 
$(document).ready(function(){
setInterval(function(){
      $("#card_grafico3colunas")
}, 3300);
});
</script>
<script> 
$(document).ready(function(){
setInterval(function(){
      $("#card_grafico4colunas")
}, 2000);
});
</script>




<!-- GRAFICOS -->
<!-- GRAFICOS -->
<!-- GRAFICOS -->
<!-- GRAFICOS -->




<!-- CONEXÃO ------------------------------------------------------>
<!-- CONEXÃO ------------------------------------------------------>
<!-- CONEXÃO ------------------------------------------------------>
<!-- CONEXÃO ------------------------------------------------------>



<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="shortcut icon" href="../imagens/icons.png" />
  <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="clientes.css">

</head>
<body onload="ocultar_menu()">
<div id="div_sistema_simov" style="background-color:#f2f2f2; width:100%; height:3000px; position:absolute; z-index:100000;">
<center><img src="http://atise.website/SIMOVSAT/APP/imagens/logo.png" id="img_logo" /><cemter>
</div>


@extends('Admin.Layouts.default')

@section('content')



    <div class="container-fluid page-body-wrapper">
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                 
                  <div>
      
                
<td class="post">
<button id="button_simovsat_hover"
onclick="ocultar_menu()">
<i id="menu_hover"
class="fa fa-bars" 
aria-hidden="true">
</i>
</button>
<div id="menu_div_fechar">

                     
                      <div class="btn-wrapper" id="buttons_wp">
                      
                      <a href="http://atise.website/api/alertas/exportacoes/rastreadores.php"  class="btn btn-otline-dark" id="button_simovsat"               ><i      id="icones_menu" class="fa fa-file-excel-o" aria-hidden="true"></i>   <spa id="fonte_menu">rastreador</spa></a>
                      <a href="http://atise.website/api/alertas/exportacoes/usuarios.php"      class="btn text-white me-0" id="button_simovsat"               ><i      id="icones_menu"class="fa fa-file-excel-o"  aria-hidden="true"></i>   <spa id="fonte_menu">usuario</spa></a>
                      <a href="http://atise.website/api/alertas/exportacoes/usuarios_word.php" class="btn text-white me-0" id="button_simovsat"               ><i      id="icones_menu"class="fa fa-file-word-o"   aria-hidden="true"></i>   <spa id="fonte_menu"> usuario word</spa></a>
                      <a href="http://atise.website/api/alertas/exportacoes/alertas.php"       class="btn btn-otline-dark" id="button_simovsat"               ><i      id="icones_menu" class="fa fa-file-excel-o" aria-hidden="true"></i>   <spa id="fonte_menu">alerta 72h</spa></a>
                      <a href="http://atise.website/api/alertas/exportacoes/conexoes.php"      class="btn btn-otline-dark" id="button_simovsat"               ><i      id="icones_menu"class="fa fa-file-excel-o"  aria-hidden="true"></i>   <spa id="fonte_menu">conexão off</spa></a>
                      <a href="http://atise.website/api/alertas/exportacoes/fraude.php"        class="btn btn-otline-dark" id="button_simovsat"               ><i      id="icones_menu" class="fa fa-file-excel-o" aria-hidden="true"></i>   <spa id="fonte_menu">fraude 72h</spa></a>
                      </div>
                 
                      </div>
                      </div>
                      </div>

</div>
<span id="menu_dash">
<p></p>
Dashboard
</span>
</td>
            
   

                                    

</div>

<!-- CARD DADOS -->
<div id="card_">

<a href="clients/simovsat_admin">
<div id="card_grafico_top"  
class="on-hover_grafico_usuario"
style="
background-color:#3377ff !important; 
box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px ;
cursor:pointer !important;
">
<p><i  id="icones_" style="color:#ffffff !important;"class="fa fa-user" aria-hidden="true"></i></p>
<p id="texto_dados" style="color:#ffffff !important;">Usuário</p>
<h1 style="font-size:50px; font-weight:bold !important; color:#ffffff !important;"><?php 
if ($usuários_simov == ''){echo 0;}else{ echo $usuários_simov ;}
?></h1>
</div>
</a>

<div id="card_grafico_top">
<i id="icones_"class="fa fa-car" aria-hidden="true"></i>
<p id="texto_dados">Veículo</p>

<h1 style="font-size:50px; font-weight:bold !important; color:#595959 !important;"><?php 
if ($veiculos_ == ''){echo 0;}else{ echo $veiculos_;}
?>
</h1>
</div>





</div>



<!-- CARD DADOS -->




<!-- CARD DADOS -->
<div id="card_">


<div id="card_grafico">
<p><i  id="icones_"class="fa fa-map-marker" aria-hidden="true"></i></p>
<p id="texto_dados">Online</p>
<h1 style="font-size:50px; font-weight:bold !important; color:#595959 !important;"><?php 
if ($on_line == ''){echo 0;}else{ echo $on_line;}
?></h1>
</div>






<div id="card_grafico">
<i id="icones_"class="fa fa-toggle-off" aria-hidden="true"></i>
<p id="texto_dados">Offline</p>
<h1 style="font-size:50px; font-weight:bold !important; color:#595959 !important;"><?php 
if ($off_line == ''){echo 0;}else{ echo $off_line;}
?></h1>
</div>


<div id="card_grafico">
<i id="icones_"class="fa fa-ban" aria-hidden="true"></i>

<p id="texto_dados">Nunca conectados</p>
<h1 style="font-size:50px; font-weight:bold !important; color:#595959 !important;"><?php 
if ($Inativos_bloqueados == ''){echo 0;}else{ echo $Inativos_bloqueados;}
?></h1>
</div>

</div>

<!-- CARD DADOS -->




<!-- CARD DADOS 3 COLUNAS-->
<div id="card_">


<div id="card_grafico3colunas">

<p id="texto_dados">Alerta do veículo</p>
<center>
<div id="texto_dados_">
<div id="info">
<div id="circulos_apresentacao"style="background-color:#00b300  !important;"></div>   <?php echo $ignição_on ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#006600 !important;"></div>   <?php echo $on_line ?> 
</div>



<div id="info">
<div id="circulos_apresentacao"style="background-color:#ace600 !important;"></div>  <?php echo $em_movimento ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#ff9933 !important;"></div>   <?php echo $parado ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#ff1a1a!important;"></div>   <?php echo $off_line ?>
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#b30000 !important;"></div>   <?php echo $ignição_off ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#660000 !important;"></div>   <?php echo $risco ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#1a0000 !important;"></div>   <?php echo $acesso_suspenso?> 
</div>
</div>

</center>

<div id="indetificacao_data">
<p id="identificadores_" style="background-color: #00b300!important;">ignição on</p>
<p id="identificadores_" style="background-color: #006600!important;">online</p>
<p id="identificadores_" style="background-color: #ace600!important;">em movimento</p>
<p id="identificadores_" style="background-color: #ff9933!important;">parado</p>
<p id="identificadores_" style="background-color: #ff1a1a!important;">offline</p>
<p id="identificadores_" style="background-color: #b30000!important;">ignição off</p>
<p id="identificadores_" style="background-color: #660000!important;">área de risco</p>
<p id="identificadores_" style="background-color: #1a0000!important;">acesso suspenso</p>
</div>
<center><div id="alertas_veiculos" class="grafico_"></center>
</div>


</div>
<div id="card_grafico3colunas">
<p id="texto_dados">Alerta de conexão</p>
<center>
<div id="texto_dados_">

<div id="info">
<div id="circulos_apresentacao"style="background-color:#33cc33 !important;"></div>   <?php echo $on_line  ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#00c28e !important;"></div>   <?php echo $online_menos1  ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#ff914c !important;"></div>   <?php echo $online_menos1dia ?>
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#fede59 !important;"></div>   <?php echo $online_mais1dia?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#d00f0f !important;"></div>   <?php echo $Inativos_bloqueados ?> 
</div>



</div>

</center>
<div id="indetificacao_data">
<p id="identificadores_" style="background-color: #33cc33 !important;">Online</p>
<p id="identificadores_" style="background-color: #00c28e !important;">- 1 Hora</p>
<p id="identificadores_" style="background-color: #ff914c !important;">- 1 Dia</p>
<p id="identificadores_" style="background-color: #fede59 !important;">+ 1 Dia</p>
<p id="identificadores_" style="background-color: #d00f0f !important;">Nunca Conectados</p>
</div>
<center><div id="alertas_conexao" class="grafico_"></div></center>


</div>

<!-- CARD DADOS -->





<!-- CARD DADOS 3 COLUNAS-->
<div id="card_" style="margin-left:-2px;">


<div id="card_grafico4colunas">


<p id="texto_dados">Manutenção preventiva</p>


<center>
<div id="texto_dados_">


<div id="info">
<div id="circulos_apresentacao"style="background-color:#ffcc00 !important;"></div>  <?php echo $preventiva_6h  ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#ff6600 !important;"></div>   <?php echo $preventiva_24h ?>
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#ff3300 !important;"></div>   <?php echo $preventiva_72h?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#991f00 !important;"></div>    <?php echo $preventiva_5dias ?> 
</div>


</div>

</center>
<div id="indetificacao_data">
<p id="identificadores_" style="background-color: #ffcc00 !important;"> desconexão 6h</p>
<p id="identificadores_" style="background-color: #ff6600 !important;"> desconexão 24h</p>
<p id="identificadores_" style="background-color: #ff3300 !important;"> desconexão 72h</p>
<p id="identificadores_" style="background-color: #991f00 !important;"> desconexão +5 dias</p>

</div>
<center><div id="manutencao_preventiva" class="grafico_"></div></center>
</div>

</div>




<div id="card_grafico4colunas">
<p id="texto_dados">Alerta anti-fraude</p>



<center>
<div id="texto_dados_">


<div id="info">
<div id="circulos_apresentacao"style="background-color:#ffcc00 !important;"></div>   <?php echo $DESCONECTADO ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#ff6600 !important;"></div>   <?php echo $BATERIA_48HORAS_SEM ?>
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#ff3300!important;"></div>   <?php echo $BATERIA_72HORAS_SEM ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#991f00 !important;"></div>   <?php echo $RISCO ?> 
</div>

</div>

</center>

<div id="indetificacao_data">
<p id="identificadores_" style="background-color: #ffcc00 !important;">desconectado</p>
<p id="identificadores_" style="background-color: #ff6600 !important;">bateria off 48h</p>
<p id="identificadores_" style="background-color: #ff3300 !important;">bateria off 72h</p>
<p id="identificadores_" style="background-color: #991f00 !important;">área de risco</p>
</div>
<center><div id="alertas_antifraude" class="grafico_"></div></center>
</div>





<div id="card_grafico4colunas">
<p id="texto_dados">Bateria desconectada</p>




<center>
<div id="texto_dados_">


<div id="info">
<div id="circulos_apresentacao"style="background-color:#e6e600 !important;"></div>    <?php echo $uma_horas ?>
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#ffcc00 !important;"></div>   <?php echo $duas_horas ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#ff6600 !important;"></div>    <?php echo $tres_horas?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#ff3300 !important;"></div>  <?php echo $quatro_horas?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#e60000 !important;"></div>   <?php echo $cinco_horas?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#cc0000 !important;"></div>   <?php echo $seis_horas ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#b30000  !important;"></div>   <?php echo $conectados_24horas ?> 
</div>

<div id="info">
<div id="circulos_apresentacao"style="background-color:#4d0000 !important;"></div>   <?php echo  $treze_24horas?> 
</div>
</div>

</center>
<div id="indetificacao_data">
<p id="identificadores_" style="background-color: #e6e600!important;">1 hora</p>
<p id="identificadores_" style="background-color: #ffcc00!important;">2 horas</p>
<p id="identificadores_" style="background-color: #ff6600!important;">3 horas</p>
<p id="identificadores_" style="background-color: #ff3300!important;">4 horas</p>
<p id="identificadores_" style="background-color: #e60000!important;">5 horas</p>
<p id="identificadores_" style="background-color: #cc0000!important;">6 horas</p>
<p id="identificadores_" style="background-color: #b30000!important;">24 horas</p>
<p id="identificadores_" style="background-color: #4d0000!important;">48h</p>
</div>
<center><div id="alerta_conexao_bateria" class="grafico_"></div></center>
</div>



</div>



</div>

<!-- CARD DADOS -->

</body>



@stop
@section('javascript')
<script>
//window.onresize = function(){ window.location.assign("http://simovsat.com.br/admin/users/clients") }

</script>
<script>
function ocultar_menu() {
  var x = document.getElementById("menu_div_fechar");
  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}
</script>  

<script>
    $("#div_sistema_simov").show();
    setTimeout(function() {
        $("#div_sistema_simov").hide();
    }, 2000);
</script>
@stop


<style>

/*MEDIA QUERY*/
/*MEDIA QUERY*/
/*MEDIA QUERY*/
/*MEDIA QUERY*/
/*MEDIA QUERY*/
/*MEDIA QUERY*/



@media only screen and (max-width: 1500px) {
  .grafico_{
  width: 320px !important; 
  height: 320px !important;
  margin-right:-49px !important;
  margin-left: 25px !important;
  margin-top: 29px !important;
}

.on-hover_grafico_usuario:hover{
border-bottom-right-radius:88px !important;
background-color: #11e44e  !important;
box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px!important;
}
.navbar-brand {
  float: left;
  padding: 12.5px 15px;
  font-size: 13px;
  line-height: 17px;
  height: 45px !important;
}
.navbar-main {
  font-size: 8px !important;
}
.dropbtn {
  background-color: transparent;
  color: #141414;
  padding: 16px;
  width: 90px;
  font-size: 9px !important;
  margin-top: -2px !important;
  border: none;
}
#card_grafico3colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px !important;
    margin-top:14px;
    float: left;
    margin-right:14px !important;
    height: 590px !important;
    max-width:672px ;
    width:100% !important;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
}

  #card_grafico{
    font-weight: bold;
    padding: 20px !important;
    color:#ffffff!important;
    font-size: 35px !important;
    margin-top:14px !important;
    float: left !important;
    margin-right:14px !important;
    max-width:672px ;
    height: 200px !important;
    width:100% !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }
  #card_grafico4colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:445px ;
    height: 535px !important;
    width:100% !important;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
  }


#card_grafico_top{
    font-weight: bold;
    padding: 20px;
    color:#ffffff!important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:972px ;
    height: 200px !important;
    width:100%;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }

}


@media only screen and (max-width: 1450px) {
  .grafico_{
  width: 320px !important; 
  height: 320px !important;
  margin-right:-49px !important;
  margin-left: 25px !important;
  margin-top: 29px !important;
}

.on-hover_grafico_usuario:hover{
border-bottom-right-radius:88px !important;
background-color: #11e44e  !important;
box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px!important;
}
.navbar-brand {
  float: left;
  padding: 12.5px 15px;
  font-size: 13px;
  line-height: 17px;
  height: 45px !important;
}
.navbar-main {
  font-size: 8px !important;
}
.dropbtn {
  background-color: transparent;
  color: #141414;
  padding: 16px;
  width: 90px;
  font-size: 9px !important;
  margin-top: -2px !important;
  border: none;
}
#card_grafico_top{
    font-weight: bold;
    padding: 20px;
    color:#ffffff!important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:1200px !important;
    height: 200px !important;
    width:100%;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }
#card_grafico3colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px !important;
    margin-top:14px;
    height: 404px !important;
    width:100% !important;
    max-width: 592px !important;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
}

  #card_grafico{
    font-weight: bold;
    padding: 20px !important;
    color:#ffffff!important;
    font-size: 35px !important;
    margin-top:14px !important;
    margin-right:14px !important;
    height: 200px !important;
    max-width:1200px !important;
    width:100% !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }
  #card_grafico4colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px;
    margin-top:14px;
    height: 535px !important;
    max-width:392px !important;
    width:100% !important;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
  }

}



@media only screen and (max-width: 1350px) {
  .grafico_{
  width: 320px !important; 
  height: 320px !important;
  margin-right:-49px !important;
  margin-left: 25px !important;
  margin-top: 29px !important;
}

.on-hover_grafico_usuario:hover{
border-bottom-right-radius:88px !important;
background-color: #11e44e  !important;
box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px!important;
}
.navbar-brand {
  float: left;
  padding: 12.5px 15px;
  font-size: 13px;
  line-height: 17px;
  height: 45px !important;
}
.navbar-main {
  font-size: 8px !important;
}

.dropbtn {
  background-color: transparent;
  color: #141414;
  padding: 16px;
  width: 90px;
  font-size: 9px !important;
  margin-top: -2px !important;
  border: none;
}

#card_grafico_top{
    font-weight: bold;
    padding: 20px;
    color:#ffffff!important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:1200px !important;
    height: 200px !important;
    width:100%;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }

#card_grafico3colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px !important;
    margin-top:14px;
    height: 440px !important;
    width:100% !important;
    max-width: 534px !important;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
}

  #card_grafico{
    font-weight: bold;
    padding: 20px !important;
    color:#ffffff!important;
    font-size: 35px !important;
    height: 200px !important;
    max-width:1200px !important;
    width:100% !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }
  #card_grafico4colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px;
    height: 535px !important;
    max-width:1200px!important;
    width:100% !important;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
  }

}



@media only screen and (max-width: 1200px) {
  .grafico_{
  width: 320px !important; 
  height: 320px !important;
  margin-right:-49px !important;
  margin-left: 25px !important;
  margin-top: 29px !important;
}
.navbar-brand {
  float: left;
  padding: 12.5px 15px;
  font-size: 13px;
  line-height: 17px;
  height: 45px !important;
}
.navbar-main {
  font-size: 8px !important;
}
.dropbtn {
  background-color: transparent;
  color: #141414;
  padding: 16px;
  width: 90px;
  font-size: 9px !important;
  margin-top: -2px !important;
  border: none;
}
#card_grafico_top{
    font-weight: bold;
    padding: 20px;
    color:#ffffff!important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width: 1200px !important ;
    height: 200px !important;
    width:100%;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }

.on-hover_grafico_usuario:hover{
border-bottom-right-radius:88px !important;
background-color: #11e44e  !important;
box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px!important;
}
#card_grafico3colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px !important;
    margin-top:14px;
    height: 440px !important;
    width:100% !important;
    max-width: 1200px !important;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
}

  #card_grafico{
    font-weight: bold;
    padding: 20px !important;
    color:#ffffff!important;
    font-size: 35px !important;
    height: 200px !important;
    max-width:1200px !important;
    width:100% !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }
  #card_grafico4colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px;
    height: 535px !important;
    max-width:1200px!important;
    width:100% !important;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
  }

}


@media only screen and (max-width: 900px) {
  .grafico_{
  width: 320px !important; 
  height: 320px !important;
  margin-right:-49px !important;
  margin-left: 25px !important;
  margin-top: 29px !important;
}
.navbar-brand {
  float: left;
  padding: 12.5px 15px;
  font-size: 13px;
  line-height: 17px;
  height: 45px !important;
}
.navbar-main {
  font-size: 8px !important;
}
.dropbtn {
  background-color: transparent;
  color: #141414;
  padding: 16px;
  width: 90px;
  font-size: 9px !important;
  margin-top: -2px !important;
  border: none;
}
#button_simovsat{
  width:100% !important;
  background-color:transparent !important;
  color:#595959 !important;
  text-align: left !important;
  float: left !important;
  font-size:18px !important;
  margin-left:15px !important;

}
#fonte_menu{
  color:#595959 !important;
}
#button_simovsat_hover{
  border:none !important;
  background-color:transparent;

}
#menu_hover{
    color:#595959 !important;
  }
#icones_menu{
  color:#595959 !important;
  margin-right:5px !important;
}
.on-hover_grafico_usuario:hover{
border-bottom-right-radius:88px !important;
background-color: #11e44e  !important;
box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px!important;
}
#card_grafico3colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px !important;
    margin-top:14px;
    height: 440px !important;
    width:100% !important;
    max-width: 1200px !important;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
}


#card_grafico_top{
    font-weight: bold;
    padding: 20px;
    color:#ffffff!important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:1200px !important;
    height: 200px !important;
    width:100%;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }
  #card_grafico{
    font-weight: bold;
    padding: 20px !important;
    color:#ffffff!important;
    font-size: 35px !important;
    height: 200px !important;
    max-width:1200px !important;
    width:100% !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }
  #card_grafico4colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px;
    height: 535px !important;
    max-width:1200px!important;
    width:100% !important;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
  }

}
#button_simovsat_hover{
  border:none !important;
  background-color:transparent;

}
.on-hover_grafico_usuario:hover{
border-bottom-right-radius:88px !important;
background-color: #11e44e  !important;
box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px!important;
}
/*MEDIA QUERY*/
/*MEDIA QUERY*/
/*MEDIA QUERY*/
/*MEDIA QUERY*/
/*MEDIA QUERY*/
/*MEDIA QUERY*/
#menu_dash{
margin-top:40px;
font-size: 18px;
font-weight: bold;
margin-left:16px;
padding:10px;
color:#999999;

}
.grafico_{
  margin-top:-36px;
  width: 490px; 
  height: 480px;
  
}
#indetificacao_data{
  float:left;
  padding:10px;
  margin-top:24px;
  margin-right:15px;
  
}
#identificadores_{
    background: #f00;
    max-width:70px;
    padding:10px;
    border-radius:4px;
    font-size:8px;
    color:#ffffff;
    font-weight: bold;
}



#circulos_apresentacao{
    background: #f00;
    width: 25px;
    height: 25px;
    border-radius: 50%;
}
#texto_dados_{
    font-size: 10px;
    color:#8c8c8c;
    margin: 0 auto;
    background-color: #ffffff;
    padding:10px;
    height:70px;
    border-radius:12px;
    width:100%;
    box-shadow: rgba(0, 0, 0, 0.15) 1.95px 1.95px 2.6px;
  }
#info{

    float:left;
    margin-right:15px;
    font-size:11px;
    margin-top:10px;
}
#img_logo{
margin: 0 auto;
padding:20px;
width:390px;
margin-top:350px;
}
#card_grafico3colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:890px ;
    height: 552px !important;
    width:100%;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
}

#card_grafico4colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:590px ;
    width:100%;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
}
 
#texto_dados{
    font-size: 16px;
    padding:3px;
    color:#595959;
}
#button_ver{
    padding:9px;
    background: transparent !important;
    border-radius: 36px;
    text-align: center;
    font-weight: bold;
    border:none;
    font-size: 11px;
    max-width:150px;
    float:right;
}
#icones_{

    padding:3px;
    font-size: 30px;
    color:#595959;

}

#card_{
    width:100%;
    margin:0 auto;
    padding:20px;
    margin-top:100px;
}

#card_grafico_top{
    font-weight: bold;
    padding: 20px;
    color:#ffffff!important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:890px;
    height: 200px !important;
    width:100%;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }

  #card_grafico{
    font-weight: bold;
    padding: 20px;
    color:#ffffff!important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:590px;
    height: 200px !important;
    width:100%;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

  }
  /*************************** ESTILOS  DAS VIEWS ********************/
  /*************************** ESTILOS  DAS VIEWS ********************/
  /*************************** ESTILOS  DAS VIEWS ********************/
  .sidebar .nav:not(.sub-menu) > .nav-item {
      margin-top: 40px !important;
    }
  #th_csv{
      background-color:#eee !important;
      color:#000000;
      border:none;
      text-align: center !important;
    }
  
    #th_csv_resultados{
      background-color:#ffffff !important;
      color:#000000;
      border:none;
      font-weight: bold;
      font-size: 10px;
      text-align: left;
    }
  
  #color_simovsat{
      background-color: #b30000;
      color:#ffffff !important;
      padding:7px;
      font-weight: bold;
      margin-left:12px;
      font-size: 19px;
      border-radius: 7px;
  }
  #color_simovsat_{
      background-color: #b30000;
      color:#ffffff !important;
      padding:7px;
      font-weight: bold;
      margin-top:30px;
      border:none;
      margin-bottom:10px;
      font-size: 13px;
      border-radius: 7px;
  }
  
  #button_simovsat{
     background-color:#3377ff;
     border-radius: 3px;
     padding:11px;
     font-weight: bold;
     color:#ffffff  !important;
     margin-right:10px;
     border:none;
     width:160px;
     margin-top:20px;
  }
  #menu_hover{
    color:#3377ff; margin-right:5px; font-size:23px;
  }
  #button_simovsat_{
      background-color: #000000;
      border-radius: 12px;
      padding:11px;
      font-weight: bold;
      margin-right:10px;
      border:none;
      color:#ffffff !important;
      width:250px;
      margin-top:20px;
   }
   #font_{
      font-family:Verdana, Geneva, Tahoma, sans-serif;
      font-weight: bold;
      padding:5px;
   }
  #logo_{
      width:350px;
      height:30px;
  }
  #icons_menu{
      width:20px;
      margin-right:10px;
  }
  #card_detalhes_{
  
      background-color: #ffffff;
      padding:20px;
      border-radius: 12px;
      box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
  
  }
  #text_info_{
  
      color:#b30000 !important;
      padding:14px;
      font-weight: bold;
      font-size: 30px !important;
  }
  
  #buttons_wp{
      margin-top:20px;
      padding:10px;
      margin-bottom: 30px !important;
      margin: 0 auto;
  }
  
  #input_sistema{
      background-color: #d9d9d9;
      border-radius: 36px;
      padding:11px;
      margin-right:10px;
      border:none;
      color:#000000;
      max-width:650px;
      width:100%;
      padding:10px;
      margin-top:13px;
      margin-bottom:10px;
  
  }
  
  #input_sistema_{
      background-color: #b30000;
      border-radius: 36px;
      padding:11px;
      margin-right:10px;
      border:none;
      color:#ffffff;
      max-width:650px;
      width:100%;
      padding:10px;
      margin-top:13px;
      margin-bottom:10px;
  }
  /*************************** ESTILOS  DAS VIEWS ********************/
  /*************************** ESTILOS  DAS VIEWS ********************/
  /*************************** ESTILOS  DAS VIEWS ********************/
  

</style>
