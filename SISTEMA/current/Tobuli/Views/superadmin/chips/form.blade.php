@extends('Admin.Layouts.default')

@section('content')

        <div class="container">
         
         
            @if(isset($chip))
         
                {!! Form::model($chip, ['method' => 'put', 'route' => ['chip.update', $chip->id ], 'class' => 'form-horizontal']) !!}
         
            @else
         
                {!! Form::open(['method' => 'post','route' => 'chip.store', 'class' => 'form-horizontal']) !!}
                
         
            @endif
         
            <div class="card">
                <div class="card-header">
           
                </div>
                <div class="card-body"  id="card_edit_form">
      


                    <center><img id="img_"src="https://cdn-icons-png.flaticon.com/512/4298/4298126.png" /></center>

                    <div class="col-sm-12" id="input">
                        
         
                    {!! Form::text('fornecedor', null, ['class' => 'input_', 'placeholder'=>'Nome do fornecedor']) !!}
         
                    </div>

 
           
                    <div class="col-sm-12" id="input">
           
                    {!! Form::text('operadora', null, ['class' => 'input_', 'placeholder'=>'Nome da operadora']) !!}
           
                    </div>
                    

                    <div class="col-sm-12" id="input">
               
                        {!! Form::number('numero', null, ['class' => 'input_', 'placeholder'=>'numero do chip']) !!}
                
                   </div>
                   
                   <div class="col-sm-12" id="input">
               
                        {!! Form::text('equipamento', null, ['class' => 'input_', 'placeholder'=>'identificação do equipamento']) !!}
                
                   </div>   

                   <div class="col-sm-12" id="input">
               
                        {!! Form::text('modelo', null, ['class' => 'input_', 'placeholder'=>'modelo do equipamento']) !!}
                
                   </div>


                   <div class="col-sm-12" id="input">
               
                        {!! Form::number('imei', null, ['class' => 'input_', 'placeholder'=>'imei do equipamento']) !!}
                
                   </div>            
                      

                   <center><div class="card-footer">
                    {!! Form::button('voltar', ['class'=>'rastreador_button', 'onclick' =>'windo:history.go(-1);']); !!}
                    {!! Form::submit(  isset($chip) ? 'atualizar' : 'cadastrar novo', ['class'=>'rastreador_button']) !!}
            
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
              max-width: 200px;
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
             height: 900px;
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