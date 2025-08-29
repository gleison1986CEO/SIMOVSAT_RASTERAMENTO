@extends('Admin.Layouts.default')

@section('content')

      @section('title', 'Listando todos os chips M2M')
       
      @section('content')

      <div class="container">
        <p><a href="{{ route('chip.create') }}" class="btn btn-sm" id="rastreador_button">Cadastrar</a>
        <a href="http://simovsat.com.br/admin/users/clients/importar_chips" class="btn btn-sm" id="rastreador_button">Importar</a></p>
          <table class="table table-bordered table-striped table-sm">
              <thead>
            <tr>
                <th>ID</th>
                <th>fornecedor</th>
                <th>operadora</th>
                <th>número</th>
                <th>data/hora</th>
  
                <th>
             
                </th>
            </tr>
              </thead>
              <tbody>
            @forelse($chip as $chips)
                <tr>
                <td>{{ $chips->id }}</td>
                <td>{{ $chips->fornecedor}}</td>
                <td>{{ $chips->operadora}}</td>
                <td>{{ $chips->numero}}</td>
                <td>{{ $chips->created_at}}</td>
                <td>
              <a href="{{ route('chip.edit', ['id' => $chips->id]) }}" class="btn  btn-sm" id="rastreador_button">editar</a>
              <form method="POST" action="{{ route('chip.destroy', ['id' => $chips->id]) }}" style="display: inline" onsubmit="return confirm('Deseja excluir este chip M2M?');" >
                  <input type="hidden" name="_method" value="delete" >
                  <button id="excluir_" class="btn btn-sm"><i id="icones_"class="fa fa-trash-o" aria-hidden="true"></i></button>
              </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">nenhum CHIP M2M encontrado aqui...Porfavor cadastre seu primeiro Chip</td>
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