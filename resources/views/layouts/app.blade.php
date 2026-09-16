<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', \App\Models\Setting::get('shop_name', 'Gundam Shop'))</title>
    
    <!-- Prevent flash of wrong theme -->
    <script>
      (function() {
        var theme = localStorage.getItem('theme');
        if (theme === 'light') {
          document.documentElement.classList.add('light-theme');
        }
      })();
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="bg-bg-primary text-text-primary min-h-screen font-body">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[100] focus:px-4 focus:py-2 focus:bg-bg-secondary focus:text-white focus:rounded-lg focus:border focus:border-accent-blue">Bỏ qua tới nội dung chính</a>
    @include('components.header')

    <main id="main-content" class="min-h-screen">
        @yield('content')
    </main>
    
    @include('components.footer')
    
    @stack('scripts')
</body>
</html>
