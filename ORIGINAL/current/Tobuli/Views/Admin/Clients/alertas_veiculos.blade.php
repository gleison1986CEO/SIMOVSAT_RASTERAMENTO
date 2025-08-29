
<div id="div_sistema_simov" style="background-color:#f2f2f2; width:100%; height:3000px; position:absolute; z-index:100000;">
<center><img src="http://atise.website/SIMOVSAT/APP/imagens/logo.png" id="img_logo" /><cemter>
</div>


@extends('Admin.Layouts.default')

@section('content')

<!--MENU -->
<ul>
  <li><a href="alertas_veiculos">Alerta de veiculos</a></li>
  <li><a href="alertas_fraude">Alerta de fraude</a></li>
  <li><a href="alertas_conexao">Alerta de conexao</a></li>
  <li><a href="alertas_cerca">Alerta de cerca</a></li>

</ul>
<!--MENU -->



 ALERTA VEÍCULOS



 @stop
@section('javascript')
<script>
    $("#div_sistema_simov").show();
    setTimeout(function() {
        $("#div_sistema_simov").hide();
    }, 2000);
</script>
@stop


<style>


@media only screen and (max-width: 1200px) {
   
}
#img_logo{
margin: 0 auto;
padding:20px;
width:390px;
margin-top:250px;
}
  #card_grafico3colunas{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:485px;
    height: 400px !important;
    width:100%;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
  }
  #texto_dados{
    font-size: 13px;
    padding:2px;
    color:#404040;
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
  }

  #card_{
    width:100%;
    margin:0 auto;
    padding:20px;
    margin-top:100px;
  }
  #card_grafico{
    font-weight: bold;
    padding: 20px;
    color:#404040 !important;
    font-size: 35px;
    margin-top:14px;
    float: left;
    margin-right:14px;
    max-width:359px;
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
     background-color: #ffffff;
     border-radius: 3px;
     padding:11px;
     font-weight: bold;
     color:#0071c1 !important;
     margin-right:10px;
     border:none;
     width:160px;
     margin-top:20px;
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