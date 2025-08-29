<table class="table">
    <tbody>
    @foreach($events as $key => $event)
        <tr>
            <!--VER EVENT TYPE REMOVER APENAS SUGERIDO-->
            <td class="text-left">{{ $event['title']}}</td>
            <!--VER EVENT TYPE -->
            <td id="ultimos_alertas__"class="text-right"><b>{{ $event['count'] ?? 0 }} {{ trans('front.times') }}</b></td>
        </tr>
    @endforeach
    </tbody>
</table>