@extends('Admin.Layouts.default')
@section('content')
    <div class="container">
        <div class="row">
            <div id="card_edit_form">

                

                    <div class="panel-body">
                        <center><img id="img_"src="https://cdn-icons-png.flaticon.com/512/4298/4298126.png" /></center>
                        <p id="sucesso">VCS ESTA REPETINDO DADOS NO SEU ARQUIVO OU CADASTRO PORFAVOR CONFIRA E TENTE NOVAMENTE</p>
                    </div>
                </div>
            
        </div>
    </div>
@endsection


<style>
    #sucesso{
        text-align: center;
        font-weight: bold;
        font-size: 30px;
        color:#ffcc00;
    }
    #campos{
      border-radius: 5px;
      margin-right: 6px;
      width: 100%;
      max-width: 190px;
      border:none;
      padding:10px;
      color:#000000;
      background-color: #ffcc00 !important;
    }
    .upload_button{
    text-align: center;
    display: inline-block;
    padding: 6px 12px;
    background-color: #f2f2f2;
    padding:15px;
    cursor: pointer;
    width: 100%;
    max-width: 400px;
    border: none;
    border-radius: 12px;
}
    input[type="file"] {
    display: none;
}
    #img_{
      width: 100%;
      max-width: 300px;
      padding:40px;
      margin-bottom: 30px;
      margin: 0 auto;
    }
    input[type=number]::-webkit-inner-spin-button { 
     -webkit-appearance: none;

    }
  input[type=number] { 
   -moz-appearance: textfield;
   appearance: textfield;

  }

    #input{
      padding:10px;
      margin-top:5px !important;
     
      margin-bottom: 5px !important;
    }
    .input_{
      width: 100%;
      background-color: #f2f2f2;
      font-weight: bold;
      padding:20px;
      border-radius: 12px;
      border:none;
    }
    #card_edit_form{
     padding:20px;
     max-width: 500px;
     width: 100%;
     margin:  0 auto;
     height: 600px;
     margin-top: 50px;
     align-items: center ;
     background-color: #ffffff;
     margin-bottom: 90px;
     border-radius: 12px;
     box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

    }
    .rastreador_button{
      border-radius: 5px;
      margin-right: 6px;
      width: 100%;
      max-width: 600px;
      align-content: center;
      border:none;
      padding:10px;
      color:#000000;
      background-color: #ffcc00 !important;
    }
    #rastreador_button{
      border-radius: 5px;
      margin-right: 6px;
      width: 100%;
      max-width: 600px;
      border:none;
      padding:10px;
      color:#000000;
      background-color: #ffcc00 !important;
    }
    #excluir_{
      border-radius: 5px;
      margin-right: 6px;
      border:none;
      padding:10px;
      color:#ffffff;
      background-color: #ff3300 !important;
    }
    td{
      padding:19px;
      text-align: center;
    }
  </style>