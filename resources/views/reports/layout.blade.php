<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .period { color: #6b7280; margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        th.num, td.num { text-align: right; white-space: nowrap; }
        tfoot td { font-weight: bold; background: #f9fafb; }
    </style>
</head>
<body>
    <h1>@yield('title')</h1>
    <p class="period">@yield('period')</p>

    @yield('content')
</body>
</html>
