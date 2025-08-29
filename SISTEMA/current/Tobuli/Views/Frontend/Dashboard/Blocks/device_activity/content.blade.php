<div id="device_activity" 
style="width: 200px; height: 300px; margin: auto; border-radius:20px;">
</div>

<script type='text/javascript'>
    $(document).ready(function () {
        $.plot('#device_activity',
            [
                {
                    label: '{{ trans('global.online') }}',
                    data: {{ $online }},
                    color: '#3d00ccff'
                },
                {
                    label: '{{ trans('global.offline') }}',
                    data: {{ $offline }},
                    color: '#e65c00ff'
                }
                
            ],
            {
                series: {
                    pie: {
                        innerRadius: 0.35,
                        show: true
                    }
                },
                legend: {
                    show: false,
                },
            });

        $('#device_activity').css('width', 'auto');
    });
</script>