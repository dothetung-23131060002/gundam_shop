import './bootstrap';

document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    var html = document.documentElement;
    var darkIcon = toggle.querySelector('.dark-icon');
    var lightIcon = toggle.querySelector('.light-icon');

    function updateIcon() {
        if (html.classList.contains('light-theme')) {
            darkIcon.classList.remove('hidden');
            lightIcon.classList.add('hidden');
        } else {
            darkIcon.classList.add('hidden');
            lightIcon.classList.remove('hidden');
        }
    }

    updateIcon();

    toggle.addEventListener('click', function() {
        html.classList.toggle('light-theme');
        localStorage.setItem('theme', html.classList.contains('light-theme') ? 'light' : 'dark');
        updateIcon();
    });
});
