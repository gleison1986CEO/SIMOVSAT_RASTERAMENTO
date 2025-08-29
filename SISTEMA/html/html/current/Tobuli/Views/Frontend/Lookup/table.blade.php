@php /** @var \Yajra\DataTables\Html\Builder $html */ @endphp
@php /** @var \Tobuli\Lookups\LookupTable $lookup */ @endphp

<div id="datatable-filter-{{ $html->getTableId() }}">
    @foreach($lookup->getFilters() as $filter)
        <p>{!! $filter->renderFormGroup() !!}</p>
    @endforeach
</div>

<script>
    $(document).ready( function() {
        let $filters = $('#datatable-filter-{{ $html->getTableId() }}');

        $('input, select', $filters).on('change', function() {
            $('#{{ $html->getTableId() }}').DataTable().draw();
        });
    });
</script>

{!! $html->table() !!}
{!! $html->scripts() !!}

