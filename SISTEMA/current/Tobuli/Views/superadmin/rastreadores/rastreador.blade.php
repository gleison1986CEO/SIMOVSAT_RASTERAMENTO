@extends('Admin.Layouts.default')

@section('content')

      @section('title', 'Listando todos os rastreadores')
       
      @section('content')
      
      <div class="container">


        <form action="{{ route('rastreadores.index') }}" method="GET" id="FORM">
          <input type="text" name="search" placeholder="*Faça uma pesquisa..."ID="SEARCH"required/>
          <button type="submit" ID="BUTTONSEARCH">Pesquisar</button>
        </form>
        
          
        <p><a href="{{ route('rastreadores.create') }}" class="btn btn-sm" id="rastreador_button">Cadastrar</a>
        <a href="http://simovsat.com.br/admin/users/clients/importar_rastreadores" class="btn btn-sm" id="rastreador_button_import">Importar</a>
        <a href="http://simovsat.com.br/admin/users/clients/chip" class="btn btn-sm" id="rastreador_button">Novo Chip</a>
        <a href="http://simovsat.com.br/admin/users/clients/estoque" class="btn btn-sm" id="rastreador_button_estoque">Estoque</a></p>  
        
          <table class="table table-bordered table-striped table-sm">
              <thead>
            <tr>
                <th>ID</th>
                <th>MODELO RASTREADOR</th>
                <th>IMEI RASTREADOR</th>
                <th>DATA/HORA</th>
                <th>CONDIÇÕES DO EQUIPAMENTO</th>

  
                <th>
           
                </th>
            </tr>
              </thead>
              <tbody>
            @forelse($rastreador as $rastreadores)
                <tr>
                <td>{{ $rastreadores->id }}</td>
                <td>{{ $rastreadores->modelo}}</td>
                <td>{{ $rastreadores->imei}}</td>
                <td>{{ $rastreadores->created_at}}</td>
                <td>{{ $rastreadores->equipamento}}</td>

                <td>
              <a href="{{ route('rastreadores.edit', ['id' => $rastreadores->id]) }}" class="btn btn-sm" id="rastreador_button">editar</a>
              <form method="POST" action="{{ route('rastreadores.destroy', ['id' => $rastreadores->id]) }}" style="display: inline" 
                onsubmit="return confirm('Deseja excluir este rastreador?');" >
                  <input type="hidden" name="_method" value="delete" >
                  <button id="excluir_"class="btn btn-sm"><i id="icones_"class="fa fa-trash-o" aria-hidden="true"></i>
                  </button>
              </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">nenhum rastreador foi encontrado aqui ...</td>
            </tr>
            @endforelse
              </tbody>
          </table>
      </div>
      @endsection

      <style>
   #FORM{
          padding: 12px;
          margin-top: 20px;
          margin-bottom: 20px;
          background-color: #bfbfbf;
          border-radius: 10px;
        }
        #SEARCH{
          border: none;
          padding: 10px;
          width: 100%;
          max-width: 900px;
          border-radius: 20px;
        }
        #BUTTONSEARCH{
          background-color: #4c00ff !important;
          border: none;
          padding: 10px;
          border-radius: 20px;
          color: #fff;
          width: 100%;
          max-width: 200px;
        }       
        #rastreador_button_import{
          border-radius: 5px;
          margin-right: 6px;
          border:none;
          padding:10px;
          color:#000000;
          background-color: #4c00ffff !important;
        }        
        #rastreador_button{
          border-radius: 5px;
          margin-right: 6px;
          border:none;
          padding:10px;
          color:#000000;
          background-color: #ffcc00 !important;
        }
        #rastreador_button_estoque{
          border-radius: 5px;
          margin-right: 6px;
          border:none;
          padding:10px;
          color:#ffffff;
          background-color: #c91e00ff !important;
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