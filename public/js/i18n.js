/**
 * Talentos i18n v4 — Seamless Google Translate
 * Hides page until translation completes, then fades in smoothly
 */

const I18N = {
    currentLang: localStorage.getItem('talentos_lang') || 'fr',

    _setCookie(lang) {
        const val = lang === 'en' ? '/fr/en' : '';
        document.cookie = 'googtrans=' + val + '; path=/';
        document.cookie = 'googtrans=' + val + '; path=/; domain=' + location.hostname;
        document.cookie = 'googtrans=' + val + '; path=/; domain=.' + location.hostname;
    },

    _clearCookie() {
        const exp = 'expires=Thu, 01 Jan 1970 00:00:00 UTC';
        document.cookie = 'googtrans=; ' + exp + '; path=/;';
        document.cookie = 'googtrans=; ' + exp + '; path=/; domain=' + location.hostname;
        document.cookie = 'googtrans=; ' + exp + '; path=/; domain=.' + location.hostname;
    },

    /** Reveal page with a smooth fade */
    _revealPage() {
        document.documentElement.classList.remove('notranslate-pending');
        document.body.classList.remove('i18n-loading');
        document.body.classList.add('i18n-ready');
    },

    loadGoogleTranslate() {
        if (this.currentLang === 'en') {
            this._setCookie('en');
        }

        const wrap = document.createElement('div');
        wrap.id = 'google_translate_element';
        wrap.style.cssText = 'display:none';
        document.body.appendChild(wrap);

        window.googleTranslateElementInit = () => {
            new google.translate.TranslateElement({
                pageLanguage: 'fr',
                includedLanguages: 'fr,en',
                autoDisplay: false
            }, 'google_translate_element');

            // Watch for translation to complete
            if (I18N.currentLang === 'en') {
                const observer = new MutationObserver(() => {
                    const html = document.documentElement;
                    if (html.classList.contains('translated-ltr') || html.classList.contains('translated-rtl') || html.lang === 'en') {
                        observer.disconnect();
                        // Small delay for rendering to finish
                        requestAnimationFrame(() => I18N._revealPage());
                    }
                });
                observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'lang'] });
                // Safety timeout — reveal after 3s max even if detection fails
                setTimeout(() => I18N._revealPage(), 3000);
            } else {
                I18N._revealPage();
            }
        };

        const s = document.createElement('script');
        s.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        document.head.appendChild(s);
    },

    setLang(lang) {
        if (lang === this.currentLang) return;
        this.currentLang = lang;
        localStorage.setItem('talentos_lang', lang);

        if (lang === 'en') {
            this._setCookie('en');
        } else {
            this._clearCookie();
        }
        location.reload();
    },

    updateToggle() {
        document.querySelectorAll('.lang-toggle').forEach(toggle => {
            toggle.querySelectorAll('.lang-btn').forEach(btn => {
                btn.classList.toggle('active', btn.classList.contains('lang-' + this.currentLang));
            });
        });
    },

    createToggleHTML() {
        return `
        <div class="lang-toggle" role="group" aria-label="Language">
            <button type="button" class="lang-btn lang-fr ${this.currentLang === 'fr' ? 'active' : ''}" onclick="I18N.setLang('fr')" title="Français">
                <span class="lang-flag">🇫🇷</span><span class="lang-label">FR</span>
            </button>
            <button type="button" class="lang-btn lang-en ${this.currentLang === 'en' ? 'active' : ''}" onclick="I18N.setLang('en')" title="English">
                <span class="lang-flag">🇬🇧</span><span class="lang-label">EN</span>
            </button>
        </div>`;
    },

    init() {
        document.querySelectorAll('.lang-toggle-mount').forEach(mount => {
            mount.innerHTML = this.createToggleHTML();
        });
        this.updateToggle();
        this.loadGoogleTranslate();
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => I18N.init());
} else {
    I18N.init();
}
