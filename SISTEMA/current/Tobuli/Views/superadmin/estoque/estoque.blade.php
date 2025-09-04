@extends('Admin.Layouts.default')

@section('content')

      @section('title', 'Itens do estoque')
       
      @section('content')
      
      <div class="container">


        <form action="{{ route('estoque.index') }}" method="GET" id="FORM">
          <input type="text" name="search" placeholder="*Faça uma pesquisa..."ID="SEARCH"required/>
          <button type="submit" ID="BUTTONSEARCH">Pesquisar</button>
        </form>
        
        <p><a href="{{ route('estoque.create') }}" class="btn btn-sm" id="rastreador_button">Cadastrar</a>
        <a href="http://simovsat.com.br/admin/users/clients/importar_estoque" class="btn btn-sm" id="rastreador_button_import">Importar Estoque</a></p>

          <table class="table table-bordered table-striped table-sm">
              <thead>
            <tr>
                <th>STATUS</th>
                <th>ID</th>
                <th>ICCID</th>
                <th>CHIP/M2M</th>
                <th>IMEI RASTREADOR</th>
                <th>MODELO RASTREADOR</th>
                <th>IDENTIFICAÇÃO</th>
                <th>DATA/HORA</th>

  
                <th>
           
                </th>
            </tr>
              </thead>
              <tbody>
            @forelse($estoque as $estoques)
                <tr>
                <td>@if($estoques->status == "ativo")<span id="ATIVO">ATIVO</span>@else<span id="INATIVO">INATIVO</span></td>@endif
                <td>{{ $estoques->id}}</td>
                <td>{{ $estoques->iccid}}</td>
                <td>{{ $estoques->chip}}</td>
                <td>{{ $estoques->imei}}</td>
                <td>{{ $estoques->modelo}}</td>
                <td>{{ $estoques->hash}}</td>
                <td>{{ $estoques->created_at}}</td>

                <td>
              <a href="{{ route('estoque.edit', ['id' => $estoques->id]) }}" class="btn btn-sm" id="rastreador_button">Atualizar</a>
              <form method="POST" action="{{ route('estoque.destroy', ['id' => $estoques->id]) }}" style="display: inline" 
                onsubmit="return confirm('Deseja excluir?');" >
                  <input type="hidden" name="_method" value="delete" >
                  <button id="excluir_"class="btn btn-sm"><i id="icones_"class="fa fa-trash-o" aria-hidden="true"></i>
                  </button>
              </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">nenhum estoque encontrado no momento...</td>
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
        #ATIVO{
          border-radius: 5px;
          margin-right: 6px;
          border:none;
          padding:10px;
          color:#000000;
          background-color: #06d311ff !important;
        }        
        #INATIVO{
          border-radius: 5px;
          margin-right: 6px;
          border:none;
          padding:10px;
          color:#ffffff;
          background-color: #0906d3ff !important;
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
          color:#ffffff;
          background-color: #0906d3ff !important
        }                
        #rastreador_button_estoque{
          border-radius: 5px;
          margin-right: 6px;
          border:none;
          padding:10px;
          color:#ffffffff;
          background-color: #ffffffff !important;
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