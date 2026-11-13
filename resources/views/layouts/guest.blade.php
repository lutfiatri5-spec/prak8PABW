<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
 @include('layouts.partials.head')
</head>
<body>
 <div class="container">
 <div class="row justify-content-center align-items-center
min-vh-100 py-4">
 <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
 <div class="text-center mb-4">
 <a href="{{ route('login') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none text-dark">
 <span class="logo"><i class="bi bi-mortarboard"></i></span>
 <h5 class="fw-semibold mb-0">{{ config('app.name', 'Laravel') }}</h5>
 </a>
 </div>
 <div class="card shadow-sm">
 <div class="card-body p-4">
 @yield('content')
 </div>
 </div>
 </div>
 </div>
 </div>
 @include('layouts.partials.scripts')
</body>
</html>