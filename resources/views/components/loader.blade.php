{{--
    Global Loading Overlay — shown for non-audio form submissions.
    Audio uploads use XHR with the upload-progress widget instead.
--}}
<div
    id="global-loader"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm"
    style="display:none"
    aria-live="polite"
    role="status"
>
    <div class="bg-white rounded-2xl shadow-2xl px-10 py-8 flex flex-col items-center gap-4 min-w-[220px]">
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

{{-- ── Upload Progress Widget (bottom-right, audio uploads only) ──────────── --}}
<div
    id="upload-progress-widget"
    class="fixed bottom-5 right-5 z-[9998] w-72 rounded-2xl shadow-2xl overflow-hidden"
    style="display:none"
    role="status"
    aria-live="polite"
>
    {{-- Header --}}
    <div class="bg-gray-900 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg id="upw-icon-upload" class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <svg id="upw-icon-processing" class="w-4 h-4 text-amber-400 animate-spin hidden" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" stroke-dasharray="40" stroke-dashoffset="15" stroke-linecap="round"/>
            </svg>
            <svg id="upw-icon-done" class="w-4 h-4 text-green-400 hidden" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span id="upw-title" class="text-white text-sm font-semibold">Uploading Audio</span>
        </div>
        <span id="upw-pct" class="text-purple-300 text-sm font-bold tabular-nums">0%</span>
    </div>

    {{-- Progress bar --}}
    <div class="bg-gray-800 h-1.5">
        <div id="upw-bar" class="h-1.5 bg-gradient-to-r from-purple-500 to-purple-400 transition-all duration-300" style="width:0%"></div>
    </div>

    {{-- Body --}}
    <div class="bg-gray-800 px-4 py-3">
        <p id="upw-label" class="text-gray-300 text-xs">Preparing…</p>
        <p id="upw-sublabel" class="text-gray-500 text-xs mt-0.5 hidden">Don't close or refresh this page</p>
    </div>
</div>

<script>
(function () {
    // ── Global loader (non-audio forms) ─────────────────────────────────────
    const overlay = document.getElementById('global-loader');
    const gLabel  = document.getElementById('global-loader-label');

    function showLoader(msg) {
        gLabel.textContent = msg || 'Processing…';
        overlay.style.display = 'flex';
    }
    function hideLoader() {
        overlay.style.display = 'none';
    }

    window.showLoader = showLoader;
    window.hideLoader = hideLoader;

    // ── Upload progress widget ───────────────────────────────────────────────
    const widget    = document.getElementById('upload-progress-widget');
    const upwBar    = document.getElementById('upw-bar');
    const upwPct    = document.getElementById('upw-pct');
    const upwTitle  = document.getElementById('upw-title');
    const upwLabel  = document.getElementById('upw-label');
    const upwSub    = document.getElementById('upw-sublabel');
    const iconUp    = document.getElementById('upw-icon-upload');
    const iconProc  = document.getElementById('upw-icon-processing');
    const iconDone  = document.getElementById('upw-icon-done');

    function setWidgetState(state, pct, label) {
        widget.style.display = 'block';
        upwBar.style.width   = pct + '%';
        upwPct.textContent   = pct + '%';
        upwLabel.textContent = label;

        if (state === 'uploading') {
            upwTitle.textContent = 'Uploading Audio';
            iconUp.classList.remove('hidden');
            iconProc.classList.add('hidden');
            iconDone.classList.add('hidden');
            upwSub.classList.remove('hidden');
        } else if (state === 'processing') {
            upwTitle.textContent = 'Optimising Audio';
            upwPct.textContent   = '';
            iconUp.classList.add('hidden');
            iconProc.classList.remove('hidden');
            iconDone.classList.add('hidden');
        } else if (state === 'done') {
            upwTitle.textContent = 'Ready!';
            upwPct.textContent   = '✓';
            iconUp.classList.add('hidden');
            iconProc.classList.add('hidden');
            iconDone.classList.remove('hidden');
            upwSub.classList.add('hidden');
            setTimeout(() => { widget.style.display = 'none'; }, 3000);
        } else if (state === 'failed') {
            upwTitle.textContent = 'Transcoding Failed';
            iconUp.classList.add('hidden');
            iconProc.classList.add('hidden');
            iconDone.classList.add('hidden');
        }
    }

    /**
     * Upload a form via XHR showing real progress.
     * @param {HTMLFormElement} form
     */
    function xhrUpload(form) {
        const fd  = new FormData(form);
        const xhr = new XMLHttpRequest();

        // ── Upload phase ────────────────────────────────────────────────────
        xhr.upload.addEventListener('loadstart', function () {
            setWidgetState('uploading', 0, 'Starting upload…');
        });

        xhr.upload.addEventListener('progress', function (e) {
            if (! e.lengthComputable) return;
            const pct    = Math.round((e.loaded / e.total) * 100);
            const loaded = (e.loaded / 1_048_576).toFixed(1);
            const total  = (e.total  / 1_048_576).toFixed(1);
            setWidgetState('uploading', pct, loaded + ' MB of ' + total + ' MB');
        });

        xhr.upload.addEventListener('load', function () {
            setWidgetState('processing', 100, 'Uploaded! Starting optimisation…');
        });

        // ── Response phase ──────────────────────────────────────────────────
        xhr.addEventListener('load', function () {
            // Server redirected or returned success — reload the page
            // so the new episode appears with its status badge.
            window.location.reload();
        });

        xhr.addEventListener('error', function () {
            setWidgetState('failed', 100, 'Network error — please try again.');
            setTimeout(() => { widget.style.display = 'none'; }, 5000);
        });

        xhr.open(form.method || 'POST', form.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(fd);
    }

    // ── Wire up audio upload forms & regular forms ───────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(function (form) {
            if (form.dataset.noLoader !== undefined) return;

            const audioInput = form.querySelector('input[type="file"][name="audio_file"]');

            if (audioInput) {
                // Audio form → XHR with progress
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    xhrUpload(form);
                });
            } else {
                // Regular form → global overlay
                form.addEventListener('submit', function () {
                    const hasFile = form.querySelector('input[type="file"]') !== null;
                    const imgInput = form.querySelector('input[type="file"][name="thumbnail"]');
                    let msg = 'Saving…';
                    if (hasFile) msg = imgInput ? 'Uploading image to S3…' : 'Uploading file…';
                    showLoader(msg);
                });
            }
        });

        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { hideLoader(); widget.style.display = 'none'; }
        });
    });
})();
</script>
