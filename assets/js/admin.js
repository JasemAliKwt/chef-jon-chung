/**
 * Sir Jon's Kitchen — Admin Panel JS
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── Sidebar Toggle (Mobile) ────────────
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
    
    // ── Auto-dismiss flash messages ────────
    const flashes = document.querySelectorAll('.flash-message');
    flashes.forEach(flash => {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-10px)';
            flash.style.transition = 'all 0.3s ease';
            setTimeout(() => flash.remove(), 300);
        }, 5000);
    });
    
    // ── YouTube URL preview ────────────────
    const ytInput = document.getElementById('youtube_url');
    if (ytInput) {
        ytInput.addEventListener('change', () => {
            const url = ytInput.value.trim();
            const existingPreview = ytInput.parentElement.querySelector('.video-preview');
            
            if (existingPreview) existingPreview.remove();
            
            const videoId = extractYouTubeId(url);
            if (videoId) {
                const preview = document.createElement('div');
                preview.className = 'video-preview';
                preview.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>`;
                ytInput.parentElement.appendChild(preview);
            }
        });
    }
});

/**
 * Extract YouTube video ID from URL
 */
function extractYouTubeId(url) {
    if (!url) return null;
    const patterns = [
        /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/live\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/,
        /^([a-zA-Z0-9_-]{11})$/
    ];
    for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match) return match[1];
    }
    return null;
}
