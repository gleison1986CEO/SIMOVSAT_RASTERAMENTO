<!DOCTYPE html>
<html lang="{{ Language::iso() }}" class="no-js">

<head>
    <meta charset="utf-8"/>
    <title>SIMOVSAT</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <link rel="shortcut icon" href="https://simovsat.com.br/images/logo-main.png?t=1684507439" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="{{ asset_resource('assets/css/'.Appearance::getSetting('template_color').'.css') }}" />

    @yield('styles')
</head>

<body class="admin-layout">

<div class="header">
    <nav class="navbar navbar-main navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-header-navbar-collapse" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                @if ( Appearance::assetFileExists('logo') )
                <a class="navbar-brand"href="#"><img src="https://simovsat.com.br/images/logo-main.png?t=1684507439"></a>
                @endif

              
            </div>

            <div class="collapse navbar-collapse" id="bs-header-navbar-collapse">
              
                
                <ul class="nav navbar-nav navbar-right">

                    <li>


                        <div class="dropdown">
                            <button class="dropbtn" style="background-color:transparent; border:2px solid red; padding:4px; border-radius:7px; margin-top:6px;">Estoque</button>
                            <div class="dropdown-content">
                                <!-- <a href="http://simovsat.com.br/admin/users/clients/painel_estatisticas"><i class="fa fa-bar-chart" style="margin-right:6px !important;  color: #000000 !important" aria-hidden="true"></i>estatísticas</a>-->
                                <a href="http://simovsat.com.br/admin/users/clients/chip"><i class="fa fa-mobile" style="margin-right:6px !important;  color: #000000 !important" aria-hidden="true"></i>Estoque</a>
                            </div>
                          </div>

                       
                    </li>
                  
   
                    
                    {!! getNavigation() !!}
                </ul>
               
               
            </div>
        </div>
    </nav>
</div>

<div class="content">
    <div class="container-fluid">
        @if (Session::has('success'))
            <div class="alert alert-success">
                {!! Session::get('success') !!}
            </div>
        @endif
        @if (Session::has('error'))
            <div class="alert alert-danger">
                {!! Session::get('error') !!}
            </div>
        @endif

        @yield('content')
    </div>
</div>

<div id="footer">
    <div class="container-fluid">
        <p>
            <span>{{ date('Y') }} &copy; {{ Appearance::getSetting('server_name') }}
            | {{ CustomFacades\Server::ip() }}
            | v{{ config('tobuli.version') }}
            @if (Auth::User()->isAdmin())
                @if ( $limit = CustomFacades\Server::getDeviceLimit())
                     | {{ "1-$limit " . strtolower(trans('front.objects')) }}
                @endif

                | {{ trans('front.last_update') }}: {{ Formatter::time(CustomFacades\Server::lastUpdate()) }}

                @if (CustomFacades\Server::isSpacePercentageWarning())
                    | <i style="color: red;">Server disk space is almost full</i>
                @endif
            @endif
            </span>
        </p>
    </div>
</div>

@include('Frontend.Layouts.partials.trans')

<script src="{{ asset_resource('assets/js/core.js') }}"></script>
<script src="{{ asset_resource('assets/js/app.js') }}"></script>

@include('Frontend.Layouts.partials.app')

@yield('javascript')
@stack('javascript')

<script>
    $.ajaxSetup({cache: false});
    window.lang = {
        nothing_selected: '{{ trans('front.nothing_selected') }}',
        color: '{{ trans('validation.attributes.color') }}',
        from: '{{ trans('front.from') }}',
        to: '{{ trans('front.to') }}',
        add: '{{ trans('global.add') }}'
    };
    app.lang = {!! json_encode(Language::get()) !!};
    app.initSocket();
</script>

<div class="modal" id="modalDeleteConfirm">
    <div class="contents">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 class="modal-title thin" id="modalConfirmLabel">{{ trans('admin.delete') }}</h3>
                </div>
                <div class="modal-body">
                    <p>{{ trans('admin.do_delete') }}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-main" onclick="modal_delete.del();">{{ trans('admin.yes') }}</button>
                    <button class="btn btn-side" data-dismiss="modal" aria-hidden="true">{{ trans('global.cancel') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="js-confirm-link" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                loading
            </div>
            <div class="modal-footer" style="margin-top: 0">
                <button type="button" value="confirm" class="btn btn-main submit js-confirm-link-yes">{{ trans('admin.confirm') }}</button>
                <button type="button" value="cancel" class="btn btn-side" data-dismiss="modal">{{ trans('admin.cancel') }}</button>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="modalError">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 class="modal-title thin" id="modalErrorLabel">{{ trans('global.error_occurred') }}</h3>
            </div>
            <div class="modal-body">
                <p class="alert alert-danger"></p>
            </div>
            <div class="modal-footer">
                <button class="btn default" data-dismiss="modal" aria-hidden="true">{{ trans('global.close') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modalSuccess">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 class="modal-title thin" id="modalSuccessLabel">{{ trans('global.warning') }}</h3>
            </div>
            <div class="modal-body">
                <p class="alert alert-success"></p>
            </div>
            <div class="modal-footer">
                <button class="btn default" data-dismiss="modal" aria-hidden="true">{{ trans('global.close') }}</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<style>
    .dropbtn {
      background-color: transparent;
      color: #141414;
      padding: 16px;
      width:90px;
      font-size: 14px;
      margin-top:-5px;
      border: none;
    }
    
    .dropdown {
      position: relative;
      display: inline-block;
    }
    
    .dropdown-content {
      display: none;
      padding:14px;
      position: absolute;
      width:250px;
      background-color: #f1f1f1;
      min-width: 160px;
      box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
      z-index: 1;
    }
    
    .dropdown-content a {
      color: black;
      text-decoration: none;
      padding: 20px 16px;
      text-decoration: none;
      display: block;
    }
    
    .dropdown-content a:hover {background-color: rgb(243, 243, 243)2f2;}
    
    .dropdown:hover .dropdown-content {display: block;}
    
    .dropdown:hover .dropbtn {background-color: rgb(243, 243, 243)2f2;}
    </style>
