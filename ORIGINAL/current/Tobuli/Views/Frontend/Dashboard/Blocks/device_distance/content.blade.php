<div class="row">
    <div class="col-sm-10">
        <div id="distance_travelled" style="width: 100%; height: 300px"></div>
    </div>
    <div class="col-sm-2">
        <div id="distance_travelled_legends"></div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var keys = [];
        var dates = {!! $keys !!};
        for (i in dates) {
            keys.push([i, dates[i]])
        }

        var dataset = [];
        var data = {!! $data !!};
        for (plate_number in data) {
            dataset.push({
                label: plate_number,
                data: data[plate_number]
            });
        }

        $.plot($("#distance_travelled"), dataset, {
            yaxis: {
                font: {
                    size: 12,
                    color: "#b3b3b3",
                },
                tickFormatter: function formatter(x) {
                    return x.toString() + '{{ Formatter::distance()->getUnit() }}';
                }
            },
            xaxis: {
                ticks: keys,
                autoscaleMargin: .05,
                font: {
                    size: 12,
                    color: "#b3b3b3"
                }
            },
            series: {
                shadowSize: 4,
                bars: {
                    show: true,
                    barWidth: 1.12,
                    order: 10,
                    lineWidth: 5,
                    fill: true,
                    fillColor: { colors: [ { opacity: 1 }, { opacity: 0.5 } ] }
                }
            },
            legend: {
                show: true,
                noColumns: 3,
                labelFormatter: function(label, series) {
                    return '<span>' + label + '</span>';
                },
                container: $('#distance_travelled_legends'),
                labelBoxBorderColor: '#3333cc'
            },
            grid: {
                show: true,
                borderWidth: 0,
                borderColor: '#3333cc',
                backgroundColor: '#ffffff',
            }
        });
    });
</script>
