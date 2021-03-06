<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ env('APP_NAME') }} - Digital Solutions</title>

        <!-- App Compilled Styles -->
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">

        <!-- Custom Styles -->
        <link href="{{ asset('css/style.css') }}" rel="stylesheet">
  
    </head>
    <body>
    
      <div id="app">
          
      </div>
  
      <script src="{{ mix('js/app.js') }}"></script>
      <script src="{{ mix('js/extra.js') }}"></script>
      
    </body>
  </html>
  