<div class="row" >
    @foreach($statuses as $status)
        <div class="col-xs-6 col-sm-6 col-md-2"
        >
                 <div 
                 style="
                 border-top-left-radius:15px !important;
                 border-top-right-radius:15px !important;
                 text-transform: lowercase !important;
                 color:#ffffff !important;
                 font-size:13px !important;
                 font-weight:bold !important;
                 background-color: {{ $status['color'] }};
                 padding:12px !important; 
                 text-align:center !important;
                 box-shadow: rgba(0, 0, 0, 0.15) 1.95px 1.95px 2.6px;
                 margin-top:10px;
                 "
                class="title"> 
                <i class="fa fa-signal" aria-hidden="true"></i>
                {{ $status['label'] }}</div>


            <span class="stat-box" 
            style="
            background-color:#f2f2f2; 
            border-top-left-radius:0px !important;
            border-top-right-radius:0px !important;">
                

                <div 
                style=" 
                font-size:31px !important;
                color:#808080;
            
                "
                id="alertas_device_overview"
                class="count">{{ $status['data'] }}</div>
                <div 
                style=" 
                font-size:11px !important;
                text-align:center;
                color:#808080;
                "
                id="alertas_device_overview"
                class="count">veículos</div>
                
            </span>
            <a 
            style="
            text-decoration:none;"
            href="{{ $status['url'] }}" target="_blank">
            <div 
            style="
            background-color: {{ $status['color'] }} !important;
            border-bottom-right-radius:20px !important;
            border-bottom-left-radius:20px !important;
            text-align:center;
            color:#ffffff!important;
            margin-bottom:60px;
            text-decoration:none;
            margin-top:-36px;"
            class="link" id="link__">detalhes <i class="fa fa-check" aria-hidden="true"></i>
          
            </div>
            
            </a>
        </div>
       
    @endforeach
</div>


<div class="row">
    <div class="col-sm-12">
        @include("Frontend.Dashboard.Blocks.device_overview.events")
    </div>
   
</div>
<!--
<div class="row">
    <div class="col-sm-12">
    @include("Frontend.Dashboard.Blocks.device_overview.graph")
    </div>
   
</div>
-->



<script type='text/javascript'>
    if ($('#dashboard').is(':visible'))
        setTimeout(function () {
            app.dashboard.loadBlockContent('device_overview', true);
        }, 10000);
</script>
