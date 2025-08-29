@extends('Admin.Layouts.default')

@section('content')

        <div class="container">
         
         
            @if(isset($rastreador))
         
                {!! Form::model($rastreador, ['method' => 'put', 'route' => ['rastreadores.update', $rastreador->id ], 'class' => 'form-horizontal']) !!}
         
            @else
         
                {!! Form::open(['method' => 'post','route' => 'rastreadores.store', 'class' => 'form-horizontal']) !!}
                
         
            @endif
         
            <div class="card">
                <div class="card-header">
             
                </div>
                <div class="card-body" id="card_edit_form">
                 


                    <center><img id="img_"src="https://getrak.com.br/wp-content/uploads/2021/09/features-image-1-1.png" /></center>
         


                    <div class="col-sm-12" id="input">
         
                    {!! Form::number('imei', null, ['class' => 'input_', 'placeholder'=>'IMEI']) !!}
         
                    </div>

           
           
                    <div class="col-sm-12" id="input">
           
                    {!! Form::text('modelo', null, ['class' => 'input_', 'placeholder'=>'Modelo']) !!}
           
                    </div>
                    <center> <p>* escolha o estado do equipamento</a></p></center>
                    <div class="col-sm-12" id="input">
                    <select class="input_"id="equipamento" name="equipamento">
                      <option class="input_"value="novo" selected="selected"> equipamento novo</option>
                      <option class="input_"value="usado"> equipamento usado</option>
                    </select>
                    </div>
                    
                    
                    <center><div class="card-footer">
                      {!! Form::button('voltar', ['class'=>'rastreador_button', 'onclick' =>'windo:history.go(-1);']); !!}
                      {!! Form::submit(  isset($rastreador) ? 'atualizar' : 'cadastrar', ['class'=>'rastreador_button']) !!}
              
                     </div>
                    </center>

         
                   </div>
             
              
                </div>
         
               {!! Form::close() !!}
         
        </div>
        @endsection

        <style>
          #img_{
            width: 100%;
            max-width: 270px;
            padding:30px;
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
           height: 640px;
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
            border:none;
            padding:10px;
            color:#000000;
            background-color: #ffcc00 !important;
          }
          #rastreador_button{
            border-radius: 5px;
            margin-right: 6px;
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