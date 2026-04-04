{{--
    Global Audio Player Bar
    Sits at the bottom of the page. Any episode "play" button dispatches a
    custom JS event:  window.playEpisode({ id, title, bookTitle, url })
    The bar appears, loads the audio, and starts playing.
--}}
<div
    id="audio-player-bar"
    style="display:none"
    class="fixed bottom-0 left-0 right-0 z-50 bg-gray-900 text-white shadow-2xl border-t border-white/10"
>
    <div class="flex items-center gap-4 px-4 lg:px-6 py-3">

        {{-- Thumbnail / icon --}}
        <div class="w-10 h-10 rounded-lg bg-purple-700/40 flex items-center justify-center shrink-0 overflow-hidden">
            <img id="ap-thumb" src="" alt="" class="w-full h-full object-cover hidden">
            <svg id="ap-icon" class="w-5 h-5 text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 3v10.55A4 4 0 1 0 14 17V7h4V3h-6z"/>
            </svg>
        </div>

        {{-- Title --}}
        <div class="min-w-0 w-40 lg:w-56 shrink-0">
            <p id="ap-title" class="text-sm font-semibold truncate text-white"></p>
            <p id="ap-book"  class="text-xs text-white/40 truncate"></p>
        </div>

        {{-- Audio element --}}
        <audio
            id="ap-audio"
            controls
            preload="metadata"
            class="flex-1 h-9 min-w-0"
            style="accent-color:#9333ea"
        ></audio>

        {{-- Close --}}
        <button
            onclick="closeAudioPlayer()"
            title="Close player"
            class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10 text-white/50 hover:text-white transition"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>

<script>
(function () {
    const bar   = document.getElementById('audio-player-bar');
    const audio = document.getElementById('ap-audio');
    const title = document.getElementById('ap-title');
    const book  = document.getElementById('ap-book');
    const thumb = document.getElementById('ap-thumb');
    const icon  = document.getElementById('ap-icon');

    window.playEpisode = function (ep) {
        // ep = { id, title, bookTitle, url, thumbnailUrl }
        title.textContent = ep.title      || 'Episode';
        book.textContent  = ep.bookTitle  || '';

        if (ep.thumbnailUrl) {
            thumb.src = ep.thumbnailUrl;
            thumb.classList.remove('hidden');
            icon.classList.add('hidden');
        } else {
            thumb.classList.add('hidden');
            icon.classList.remove('hidden');
        }

        // Change source and play
        audio.pause();
        audio.src = ep.url;
        audio.load();
        audio.play().catch(function () { /* autoplay blocked — user can hit play */ });

        bar.style.display = 'block';

        // Push bottom of page content up so the player doesn't overlap
        document.body.style.paddingBottom = '76px';
    };

    window.closeAudioPlayer = function () {
        audio.pause();
        audio.src = '';
        bar.style.display = 'none';
        document.body.style.paddingBottom = '';
    };
})();
</script>
