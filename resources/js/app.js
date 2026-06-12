
import Alpine from 'alpinejs';
import { createIcons } from 'lucide';

window.Alpine = Alpine;
window.lucide = { createIcons };

Alpine.start();

const renderIcons = () => createIcons();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderIcons);
} else {
    renderIcons();
}

document.addEventListener('alpine:initialized', renderIcons);
