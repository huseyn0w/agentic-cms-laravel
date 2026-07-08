@extends('cpanel.core.index')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="font-serif text-2xl text-fg">@lang('cpanel/plugins.headline')</h1>
            <p class="mt-1 text-sm text-muted">@lang('cpanel/plugins.intro')</p>
        </div>

        @include('cpanel.core.flash')

        <div class="overflow-hidden rounded-lg border border-border bg-surface">
            <div class="overflow-x-auto">
                <table class="data-table users-table w-full text-left text-sm">
                    <thead class="bg-surface-2">
                        <tr>
                            <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/plugins.headline')</x-eyebrow></th>
                            <th class="px-4 py-3"><x-eyebrow>@lang('cpanel/plugins.status')</x-eyebrow></th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($plugins as $plugin)
                        <tr class="border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 align-middle">
                                <div class="font-medium text-fg">{{ $plugin['name'] }}</div>
                                <div class="text-sm text-muted">{{ $plugin['description'] }}</div>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                @if($plugin['enabled'])
                                    <x-badge variant="success">@lang('cpanel/plugins.enabled')</x-badge>
                                @else
                                    <x-badge variant="neutral">@lang('cpanel/plugins.disabled')</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-middle text-right">
                                <form action="{{ route('cpanel_toggle_plugin') }}" method="POST" class="inline-flex">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="slug" value="{{ $plugin['slug'] }}">
                                    <input type="hidden" name="enabled" value="{{ $plugin['enabled'] ? 0 : 1 }}">
                                    <x-button type="submit" size="sm" :variant="$plugin['enabled'] ? 'ghost' : 'primary'">
                                        {{ $plugin['enabled'] ? __('cpanel/plugins.disable') : __('cpanel/plugins.enable') }}
                                    </x-button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-empty-state :headline="__('cpanel/plugins.empty')" /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
