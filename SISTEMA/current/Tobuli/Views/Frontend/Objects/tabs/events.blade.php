<div class="tab-pane-header">
    <div class="form">
        <div class="input-group">
            <div class="form-group search">
                {!!Form::text('buscar veículo', null, ['class' => 'form-control', 'id' => 'events_search_field', 'placeholder' => trans('buscar veículo'), 'autocomplete' => 'off'])!!}
            </div>
            <span class="input-group-btn">

                <button class="btn btn-default" type="button"  data-url="{!! \Tobuli\Lookups\Tables\EventsLookupTable::route('index') !!}" data-modal="events_lookup">
                    <i class="icon lookup"></i>
                </button>

                <button class="btn btn-default" type="button" data-url="{!!route('events.do_destroy')!!}" data-modal="events_do_destroy">
                    <i class="icon remove-all"></i>
                </button>
            </span>
        </div>
    </div>
</div>

<div class="tab-pane-body">
    <table class="table table-condensed">
        <thead>
            <tr>
                <th>
                    <div class="row">
                        <div class="col-xs-3 datetime">
                            data/hora
                        </div>
                        <div class="col-xs-4">
                            placa
                        </div>
                        <div class="col-xs-5">
                           evento
                        </div>
                    </div>
                </th>
                <th></th>
            </tr>
        </thead>

        <tbody id="ajax-events"></tbody>
    </table>
</div>