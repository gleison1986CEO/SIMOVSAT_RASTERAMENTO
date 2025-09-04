@extends('Admin.Layouts.default')

@section('content')

      @section('title', 'Listando todos os aparelhos')
       
      @section('content')

      <div class="container">

      
        <form action="{{ route('chip.index') }}" method="GET" id="FORM">
          <input type="text" name="search" placeholder="*Faça uma pesquisa..."ID="SEARCH"required/>
          <button type="submit" ID="BUTTONSEARCH">Pesquisar</button>
        </form>

        <p><a href="{{ route('chip.create') }}" class="btn btn-sm" id="rastreador_button">Cadastrar</a>
        <a href="http://simovsat.com.br/admin/users/clients/importar_chips" class="btn btn-sm" id="rastreador_button_import">Importar</a>
        <a href="http://simovsat.com.br/admin/users/clients/rastreadores" class="btn btn-sm" id="rastreador_button">Novo Rastreador</a>
        <a href="http://simovsat.com.br/admin/users/clients/estoque" class="btn btn-sm" id="rastreador_button_estoque">Estoque</a></p>  
        <table class="table table-bordered table-striped table-sm">
              <thead>
            <tr>
                <th>ID</th>
                <th>FORNECEDOR/EMPRESA</th>
                <th>OPERADORA</th>
                <th>TELEFONE</th>
                <th>DATA/HORA</th>
                
  
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
              <form method="POST" action="{{ route('chip.destroy', ['id' => $chips->id]) }}" style="display: inline" onsubmit="return confirm('Deseja excluir?');" >
                  <input type="hidden" name="_method" value="delete" >
                  <button id="excluir_" class="btn btn-sm"><i id="icones_"class="fa fa-trash-o" aria-hidden="true"></i></button>
              </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">Nenhum equipamento encontrado...</td>
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
        #rastreador_button{
          border-radius: 5px;
          margin-right: 6px;
          border:none;
          padding:10px;
          color:#000000;
          background-color: #ffcc00 !important;
        }
        #rastreador_button_import{
          border-radius: 5px;
          margin-right: 6px;
          border:none;
          padding:10px;
          color:#000000;
          background-color: #4c00ffff !important;
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