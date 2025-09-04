@extends('Admin.Layouts.default')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div id="card_edit_form" >
                 

              
                    <form class="form-horizontal" method="POST" action="{{ route('import_parse_estoque') }}" enctype="multipart/form-data">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('csv_file') ? ' has-error' : '' }}">
                                <center><img id="img_"src="https://getrak.com.br/wp-content/uploads/2021/09/features-image-1-1.png" /></center>


                              
                            <div class="form-group{{ $errors->has('csv_file') ? ' has-error' : '' }}">
                               <center> <label for="csv_file" class="upload_button">carregar arquivo csv</label></center>

                                <div class="col-md-6">
                                    <input id="csv_file" type="file" style="display:none;" name="csv_file" required>

                                    @if ($errors->has('csv_file'))
                                        <span class="help-block">
                                        <strong>{{ $errors->first('csv_file') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-12">
                                    <div >
                                        <label>
                                            <input type="checkbox" name="header" checked> este arquivo contem cabeçalho nos padrões do csv *?
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-12">
                                    <button type="submit" id="rastreador_button">
                                        conferir dados dos rastreadores ->
                                    </button>
                                </div>
                            </div>
                            <center> <p>* baixe o gabarito de importacao de dados do  csv  <a href="https://drive.google.com/drive/folders/1H-F2GB3gmSZStCWZ1hKYJDTWH_27lpzv?usp=sharing">'donwload'</a></p></center>
                        </form>
                    </div>
                
            </div>
        </div>
    </div>
@endsection


<style>
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