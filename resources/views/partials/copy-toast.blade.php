{{-- Petit message « copié » + fonction window.copyText(texte, message). À inclure une seule fois par page. --}}
<div id="copy-toast" class="fixed top-20 left-1/2 -translate-x-1/2 z-50 bg-inverse-surface text-inverse-on-surface px-space-md py-space-xs rounded-full shadow-lg transition-opacity duration-300 opacity-0 pointer-events-none flex items-center gap-space-xs" role="status" aria-live="polite">
    <span class="material-symbols-outlined text-[18px] text-tertiary-fixed">check_circle</span>
    <span class="font-label-md text-label-md" id="copy-toast-text"></span>
</div>
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    window.copyText = function (text, message) {
        const show = function () {
            const toast = document.getElementById('copy-toast');
            document.getElementById('copy-toast-text').textContent = message;
            toast.classList.remove('opacity-0');
            clearTimeout(window.__copyToastTimer);
            window.__copyToastTimer = setTimeout(function () { toast.classList.add('opacity-0'); }, 2200);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(show, show);
        } else {
            show();
        }
    };
</script>
