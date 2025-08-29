@extends('Admin.Layouts.default')

@section('content')

      @section('title', 'Listando todos os rastreadores')
       
      @section('content')
      
      <div class="container">
        <p><a href="{{ route('rastreadores.create') }}" class="btn btn-sm" id="rastreador_button">Cadastrar</a>
        <a href="http://simovsat.com.br/admin/users/clients/importar_rastreadores" class="btn btn-sm" id="rastreador_button">Importar</a></p>
        
          <table class="table table-bordered table-striped table-sm">
              <thead>
            <tr>
                <th>ID</th>
                <th>modelo</th>
                <th>imei</th>
                <th>data</th>
                <th>equipamento</th>

  
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