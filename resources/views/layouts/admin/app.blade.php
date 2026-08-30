<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
    @stack('styles')
</head>
<body>
<div class="d-flex">
    @include('layouts.admin.sidebar')

    <div class="flex-grow-1 d-flex flex-column" style="min-height: 100vh;">
        @include('layouts.admin.navbar')

        <main class="flex-grow-1 p-3 p-md-4">
            <div class="container-fluid">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @php
                    $pageLoaderDisabled = array_key_exists('pageLoader', $__env->getSections())
                        && in_array($__env->getSections()['pageLoader'], [false, ''], true);
                @endphp

                <div class="ch-page-loader" id="pageLoaderWrap"
                     @if ($pageLoaderDisabled) data-loader-disabled="1" @endif>
                    @unless ($pageLoaderDisabled)
                        @include('layouts.admin._skeleton')
                    @endunless

                    <div class="ch-page-content">
                        @yield('content')
                    </div>
                </div>
            </div>
        </main>

        @include('layouts.admin.footer')
    </div>
</div>

@stack('scripts')
<script src="https://cdn.jsdelivr.net/npm/dropzone@5/dist/min/dropzone.min.js"></script>
<script>
    Dropzone.autoDiscover = false;
</script>
<x-chat-widget source="admin" title="AI assistant" :show-message="false" />
</body>
</html>
