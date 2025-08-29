<div class="table-responsive">
    <table class="table table-list">
        <thead>
        <tr>
            {!! tableHeader('global.device') !!}
            {!! tableHeaderSort($items->sorting, 'connection', 'front.connection') !!}
            {!! tableHeaderSort($items->sorting, 'command', 'front.command') !!}
            {!! tableHeaderSort($items->sorting, 'status') !!}
            {!! tableHeaderSort($items->sorting, 'created_at', 'global.date') !!}
        </tr>
        </thead>
        <tbody>
        @php /** @var \Tobuli\Entities\SentCommand $item */ @endphp
        @foreach($items as $item)
            <tr>
                <td>{{ $item->device->name }}</td>
                <td>{{ $item->connection }}</td>
                <td>{{ $item->command }}</td>
                <td>{{ $item->status ? trans('global.yes') : trans('global.no') }}</td>
                <td>{{ $item->created_at }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if($items->total())
    <div class="nav-pagination">
        {!! $items->setPath(route('send_commands.logs.table'))->render() !!}
    </div>
@endif