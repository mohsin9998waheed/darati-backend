{{--
    Global Loading Overlay
    Shows automatically when any form is submitted.
    Can also be triggered manually via: window.showLoader('Custom message...')
    Label defaults to "Processing…" for plain forms and "Uploading…" for file forms.
--}}
<div
    id="global-loader"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm"
    style="display:none"
    aria-live="polite"
    role="status"
>
    <div class="bg-white rounded-2xl shadow-2xl px-10 py-8 flex flex-col items-center gap-4 min-w-[220px]">
        {{-- Animated spinner --}}
        <div class="relative w-14 h-14">
            <svg class="w-14 h-14 animate-spin text-purple-600" viewBox="0 0 50 50" fill="none">
                <circle cx="25" cy="25" r="20" stroke="currentColor" stroke-width="4" stroke-linecap="round"
                        stroke-dasharray="100" stroke-dashoffset="60" opacity="0.25"/>
                <circle cx="25" cy="25" r="20" stroke="currentColor" stroke-width="4" stroke-linecap="round"
                        stroke-dasharray="30 70" stroke-dashoffset="0"/>
            </svg>
        </div>
        <p id="global-loader-label" class="text-sm font-semibold text-gray-700 text-center"></p>
        <p class="text-xs text-gray-400">Please don't close or refresh the page</p>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('global-loader');
    const label   = document.getElementById('global-loader-label');

    function show(msg) {
        label.textContent = msg || 'Processing…';
        overlay.style.display = 'flex';
    }

    function hide() {
        overlay.style.display = 'none';
    }

    // Expose globally so any page can call window.showLoader('msg')
    window.showLoader = show;
    window.hideLoader = hide;

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                // Skip forms that have data-no-loader attribute
                if (form.dataset.noLoader !== undefined) return;

                // Detect if the form has a file input
                const hasFile = form.querySelector('input[type="file"]') !== null;

                // Choose message based on content
                let msg = 'Processing…';
                if (hasFile) {
                    const audioInput = form.querySelector('input[type="file"][name="audio_file"]');
                    const imgInput   = form.querySelector('input[type="file"][name="thumbnail"]');
                    if (audioInput) msg = 'Uploading audio to S3…';
                    else if (imgInput) msg = 'Uploading image to S3…';
                    else msg = 'Uploading file…';
                } else {
                    // For simple POST forms (approve, reject, delete), only show briefly
                    msg = 'Saving…';
                }

                show(msg);
            });
        });

        // Hide if the page loads back after a validation error (browser back/reload)
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) hide();
        });
    });
})();
</script>
