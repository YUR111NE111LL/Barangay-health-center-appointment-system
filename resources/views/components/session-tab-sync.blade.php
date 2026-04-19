{{-- When another tab logs in/out on the same host + portal, localStorage updates and this tab reloads so the UI matches the shared session cookie. Same-tab: only sync storage — do not reload (avoids flicker and broken UI when the key/user id briefly disagrees). --}}
@if(auth()->check())
<script>
(function() {
    var portal = @json($sessionPortalKey ?? 'public');
    var host = window.location.host;
    var uid = document.body.getAttribute('data-current-user-id');
    var key = 'bhcas_uid_' + host + '_' + portal;
    if (!uid) {
        return;
    }
    localStorage.setItem(key, String(uid));
    window.addEventListener('storage', function(e) {
        if (e.key !== key) {
            return;
        }
        if (e.newValue === null || e.newValue !== String(uid)) {
            location.reload();
        }
    });
    document.querySelectorAll('form[action*="logout"]').forEach(function(f) {
        f.addEventListener('submit', function() {
            try {
                localStorage.removeItem(key);
            } catch (err) {}
        });
    });
})();
</script>
@endif
