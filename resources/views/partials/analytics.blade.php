{{--
    Plausible Analytics — cookieless, privacy-first, aggregate-only.
    No cookies and no personal data, so no consent banner is required.
    See docs/system-design.md §11.

    Only loads when a domain is configured, and never in local/testing, so
    dev traffic is not counted. Start on the managed plan; self-host later by
    pointing PLAUSIBLE_SRC at your own instance.
--}}
@if(config('services.plausible.domain') && ! app()->environment(['local', 'testing']))
    <script
        defer
        data-domain="{{ config('services.plausible.domain') }}"
        src="{{ config('services.plausible.src', 'https://plausible.io/js/script.js') }}"></script>
@endif
