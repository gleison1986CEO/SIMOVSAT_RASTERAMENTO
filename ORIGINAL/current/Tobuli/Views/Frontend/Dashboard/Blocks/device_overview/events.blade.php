<div class="panel panel-transparent">
    <div class="panel-heading">
        <div class="panel-title">
            <div class="pull-left">
           
            </div>

            <div style="
            background-color:#3377ff !important;
            padding:12px;
            color:#ffffff !important;
            border-radius:12px;
            "class="pull-right">
                <a style="color:#ffffff !important; text-decoration:none;"class="link" href="{{ \Tobuli\Lookups\Tables\EventsLookupTable::route('index') }}" target="_blank">
                    detalhes dos eventos
                </a>

                <div class="btn-group droparrow" data-position="fixed">
                <i class="fa fa-circle-o-notch" aria-hidden="true"></i>


                    <div class="dropdown-menu dropdown-menu-right">
                        <div class="options-dropdown">
                            {!! Form::open(['url' => route('dashboard.config_update'), 'method' => 'POST', 'class' => 'dashboard-config']) !!}
                            {!! Form::hidden('block', 'device_overview') !!}
                            <div class="radio">
                                {!! Form::radio("dashboard[blocks][device_overview][options][event_type]", 0, empty($event_type)) !!}
                                {!! Form::label(null, trans('front.none')) !!}
                            </div>
                            @foreach(\Tobuli\Entities\Event::getTypeTitles() as $type)
                                <div class="radio">
                                    {!! Form::radio("dashboard[blocks][device_overview][options][event_type]", $type['type'], $event_type == $type['type']) !!}
                                    {!! Form::label(null, ucfirst($type['title'])) !!}
                                </div>
                            @endforeach
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-body">
        <div class="table-responsive" >
            <table class="table table-list" >
                <thead>
                <tr>
                <th id="border_">Placa</th>
                <th id="border_2">M2M</th>
                <th id="border_2">Imei</th>
                <th id="border_2">Rastreador</th>
                <th id="border_2">Última conexao</th>
                <th id="border_2">Usuário</th>
                <th id="border_2">Veículo</th>
                <th id="border_2">Evento</th>
                <th id="border_2">Mensagem</th>
     
                </tr>
                </thead>
                <tbody >
                @if (count($events))
                    @foreach ($events as $event)
                        <tr>
                            <td>{{ $event->device->plate_number }}</td>
                            <td>{{ $event->device->sim_number }}</td>
                            <td>{{ $event->device->imei }}</td>
                            <td>{{ $event->device->model}}</td>
                            <td>{{ Formatter::time()->human($event->time) }}</td>
                            <td>{{ $event->device->name }}</td>
                            <td>{{ $event->device->additional_notes }}</td>
                            <td>{{ $event->name }}</td>
                            <td>{{ $event->detail }}</td>
                            
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td style="background-color:#ffffff;"class="no-data" colspan="6">
                            
                        <img style="width:600px;"src="https://i.pinimg.com/originals/56/c9/d7/56c9d773a346db66c907d60cbc44d9d8.gif"></img>
                        <p>Não há dados de veículos recentes! simovsat.com.br</p>    
                    </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>