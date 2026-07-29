@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php
    $logo = \App\Models\Setting::where('key', 'site_logo_dark')->value('value') ?? '/images/logo-dark.png';
@endphp
<img src="{{ $url }}{{ $logo }}" class="logo" alt="{{ config('app.name') }}" style="height: 50px; max-height: 50px; width: auto;">
</a>
</td>
</tr>
