/**
 * Talentos i18n — Bilingual FR/EN Translation Engine
 * Client-side instant language switch with localStorage persistence
 */

const I18N = {
    currentLang: localStorage.getItem('talentos_lang') || 'fr',

    // ═══════════════════════════════════════
    // Translation Dictionaries
    // ═══════════════════════════════════════
    translations: {
        // ── Sidebar Navigation (Back Office) ──
        'nav.dashboard':       { fr: 'Tableau de bord', en: 'Dashboard' },
        'nav.offers':          { fr: 'Offres', en: 'Offers' },
        'nav.applications':    { fr: 'Candidatures', en: 'Applications' },
        'nav.events':          { fr: 'Événements', en: 'Events' },
        'nav.interviews':      { fr: 'Entretiens', en: 'Interviews' },
        'nav.projects':        { fr: 'Projets', en: 'Projects' },
        'nav.courses':         { fr: 'Formations', en: 'Courses' },
        'nav.users':           { fr: 'Utilisateurs', en: 'Users' },
        'nav.tickets':         { fr: 'Tickets', en: 'Tickets' },
        'nav.view_site':       { fr: 'Voir le site', en: 'View site' },
        'nav.administration':  { fr: 'Administration', en: 'Administration' },
        'nav.recruitment':     { fr: 'Recrutement', en: 'Recruitment' },
        'nav.management':      { fr: 'Gestion', en: 'Management' },
        'nav.logout':          { fr: 'Déconnexion', en: 'Logout' },

        // ── Front Office Navigation ──
        'nav.home':            { fr: 'Accueil', en: 'Home' },
        'nav.fo_offers':       { fr: 'Offres', en: 'Jobs' },
        'nav.fo_events':       { fr: 'Événements', en: 'Events' },
        'nav.fo_courses':      { fr: 'Formations', en: 'Courses' },
        'nav.login':           { fr: 'Se connecter', en: 'Log in' },
        'nav.register':        { fr: "S'inscrire", en: 'Sign up' },
        'nav.my_account':      { fr: 'Mon compte', en: 'My account' },
        'nav.profile':         { fr: 'Profil', en: 'Profile' },
        'nav.fo_applications': { fr: 'Candidatures', en: 'Applications' },
        'nav.activities':      { fr: 'Activités', en: 'Activities' },
        'nav.my_circle':       { fr: 'Mon Cercle', en: 'My Circle' },

        // ── Topbar / Common ──
        'topbar.notifications': { fr: 'Notifications', en: 'Notifications' },
        'topbar.mark_all_read': { fr: 'Tout marquer lu', en: 'Mark all read' },
        'topbar.view_all':      { fr: 'Voir toutes les notifications', en: 'View all notifications' },
        'topbar.messages':      { fr: 'Messages', en: 'Messages' },

        // ── Page Titles ──
        'page.dashboard':      { fr: 'Tableau de bord', en: 'Dashboard' },
        'page.offers':         { fr: 'Gestion des offres', en: 'Offers Management' },
        'page.events':         { fr: 'Gestion des événements', en: 'Events Management' },
        'page.courses':        { fr: 'Gestion des formations', en: 'Courses Management' },
        'page.users':          { fr: 'Gestion des utilisateurs', en: 'Users Management' },
        'page.profile':        { fr: 'Mon Profil', en: 'My Profile' },
        'page.notifications':  { fr: 'Notifications', en: 'Notifications' },
        'page.interviews':     { fr: 'Entretiens planifiés', en: 'Scheduled Interviews' },

        // ── Buttons & Actions ──
        'btn.save':            { fr: 'Enregistrer', en: 'Save' },
        'btn.cancel':          { fr: 'Annuler', en: 'Cancel' },
        'btn.delete':          { fr: 'Supprimer', en: 'Delete' },
        'btn.edit':            { fr: 'Modifier', en: 'Edit' },
        'btn.add':             { fr: 'Ajouter', en: 'Add' },
        'btn.search':          { fr: 'Rechercher', en: 'Search' },
        'btn.apply':           { fr: 'Postuler', en: 'Apply' },
        'btn.view':            { fr: 'Voir', en: 'View' },
        'btn.close':           { fr: 'Fermer', en: 'Close' },
        'btn.confirm':         { fr: 'Confirmer', en: 'Confirm' },
        'btn.back':            { fr: 'Retour', en: 'Back' },
        'btn.create':          { fr: 'Créer', en: 'Create' },
        'btn.upload_photo':    { fr: 'Photo', en: 'Photo' },
        'btn.remove':          { fr: 'Supprimer', en: 'Remove' },

        // ── Profile Page ──
        'profile.edit':              { fr: 'Modifier le profil', en: 'Edit Profile' },
        'profile.first_name':        { fr: 'Prénom', en: 'First Name' },
        'profile.last_name':         { fr: 'Nom', en: 'Last Name' },
        'profile.professional_title':{ fr: 'Titre professionnel', en: 'Professional Title' },
        'profile.phone':             { fr: 'Téléphone', en: 'Phone' },
        'profile.location':          { fr: 'Localisation', en: 'Location' },
        'profile.experience':        { fr: "Années d'expérience", en: 'Years of Experience' },
        'profile.bio':               { fr: 'Bio / Résumé', en: 'Bio / Summary' },
        'profile.account':           { fr: 'Compte', en: 'Account' },
        'profile.email':             { fr: 'Email', en: 'Email' },
        'profile.role':              { fr: 'Rôle', en: 'Role' },
        'profile.verification':      { fr: 'Vérification', en: 'Verification' },
        'profile.verified':          { fr: 'Vérifié', en: 'Verified' },
        'profile.not_verified':      { fr: 'Non vérifié', en: 'Not verified' },
        'profile.provider':          { fr: 'Fournisseur', en: 'Provider' },
        'profile.since':             { fr: 'Depuis', en: 'Since' },
        'profile.yrs_exp':           { fr: 'ans exp.', en: 'yrs exp.' },

        // ── Notification Page ──
        'notif.unread':         { fr: 'non lue(s) sur', en: 'unread out of' },
        'notif.total':          { fr: 'au total', en: 'total' },
        'notif.mark_all':       { fr: 'Tout marquer lu', en: 'Mark all read' },
        'notif.empty':          { fr: 'Aucune notification', en: 'No notifications' },
        'notif.empty_desc':     { fr: 'Les notifications apparaîtront ici', en: 'Notifications will appear here' },
        'notif.loading':        { fr: 'Chargement...', en: 'Loading...' },

        // ── Crop Modal ──
        'crop.title':           { fr: 'Recadrer la photo', en: 'Crop Photo' },
        'crop.apply':           { fr: 'Appliquer', en: 'Apply' },
        'crop.override':        { fr: 'Utiliser quand même', en: 'Use anyway' },
        'crop.ai_analyzing':    { fr: '🤖 Analyse IA du portrait en cours...', en: '🤖 AI portrait analysis in progress...' },
        'crop.change':          { fr: 'Changer', en: 'Change' },

        // ── Dashboard Stats ──
        'stats.total_offers':    { fr: 'Offres totales', en: 'Total Offers' },
        'stats.total_events':    { fr: 'Événements', en: 'Events' },
        'stats.total_courses':   { fr: 'Formations', en: 'Courses' },
        'stats.total_users':     { fr: 'Utilisateurs', en: 'Users' },
        'stats.applications':    { fr: 'Candidatures', en: 'Applications' },
        'stats.pending':         { fr: 'En attente', en: 'Pending' },
        'stats.accepted':        { fr: 'Acceptées', en: 'Accepted' },
        'stats.rejected':        { fr: 'Refusées', en: 'Rejected' },

        // ── Table Headers ──
        'table.title':           { fr: 'Titre', en: 'Title' },
        'table.status':          { fr: 'Statut', en: 'Status' },
        'table.date':            { fr: 'Date', en: 'Date' },
        'table.actions':         { fr: 'Actions', en: 'Actions' },
        'table.name':            { fr: 'Nom', en: 'Name' },
        'table.type':            { fr: 'Type', en: 'Type' },
        'table.candidates':      { fr: 'Candidats', en: 'Candidates' },
        'table.location':        { fr: 'Lieu', en: 'Location' },

        // ── Footer ──
        'footer.platform':       { fr: 'Plateforme', en: 'Platform' },
        'footer.candidate':      { fr: 'Candidat', en: 'Candidate' },
        'footer.newsletter':     { fr: 'Newsletter', en: 'Newsletter' },
        'footer.newsletter_desc':{ fr: 'Recevez les dernières offres et actualités', en: 'Get the latest offers and news' },
        'footer.your_email':     { fr: 'Votre email', en: 'Your email' },
        'footer.jobs':           { fr: "Offres d'emploi", en: 'Job Offers' },
        'footer.create_account': { fr: 'Créer un compte', en: 'Create account' },
        'footer.rights':         { fr: '© 2026 Talentos. Tous droits réservés.', en: '© 2026 Talentos. All rights reserved.' },
        'footer.privacy':        { fr: 'Confidentialité', en: 'Privacy' },
        'footer.terms':          { fr: 'Conditions', en: 'Terms' },
        'footer.desc':           { fr: 'La plateforme de recrutement nouvelle génération. Trouvez votre emploi idéal, développez vos compétences et connectez-vous avec les meilleurs recruteurs.', en: 'The next-generation recruitment platform. Find your ideal job, develop your skills, and connect with top recruiters.' },
        'footer.faq':            { fr: 'FAQ', en: 'FAQ' },

        // ── Flash Messages ──
        'flash.profile_updated': { fr: 'Profil mis à jour avec succès.', en: 'Profile updated successfully.' },
        'flash.all_read':        { fr: 'Toutes les notifications marquées comme lues.', en: 'All notifications marked as read.' },
        'flash.avatar_removed':  { fr: 'Photo de profil supprimée.', en: 'Profile photo removed.' },

        // ── Misc ──
        'misc.language':         { fr: 'Langue', en: 'Language' },
        'misc.conversations':    { fr: 'Vos conversations', en: 'Your conversations' },
        'misc.start_chat':       { fr: 'pour démarrer', en: 'to start' },
    },

    // ═══════════════════════════════════════
    // Core Methods
    // ═══════════════════════════════════════

    /** Get a translation by key */
    t(key) {
        const entry = this.translations[key];
        if (!entry) return key;
        return entry[this.currentLang] || entry['fr'] || key;
    },

    /** Switch language */
    setLang(lang) {
        this.currentLang = lang;
        localStorage.setItem('talentos_lang', lang);
        document.documentElement.lang = lang;
        this.applyTranslations();
        this.updateToggle();
    },

    /** Apply all translations to elements with data-i18n */
    applyTranslations() {
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            const text = this.t(key);
            // Handle different element types
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                if (el.getAttribute('data-i18n-attr') === 'placeholder') {
                    el.placeholder = text;
                } else {
                    el.value = text;
                }
            } else {
                // Preserve child elements (icons etc), only replace text nodes
                const childElements = Array.from(el.children);
                if (childElements.length > 0) {
                    // Find or create text node
                    let textNode = null;
                    for (const node of el.childNodes) {
                        if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                            textNode = node;
                            break;
                        }
                    }
                    if (textNode) {
                        // Preserve leading/trailing space
                        const hadLeadSpace = textNode.textContent.startsWith(' ');
                        const hadTrailSpace = textNode.textContent.endsWith(' ');
                        textNode.textContent = (hadLeadSpace ? ' ' : '') + text + (hadTrailSpace ? ' ' : '');
                    } else {
                        // Append text after last child element
                        el.appendChild(document.createTextNode(' ' + text));
                    }
                } else {
                    el.textContent = text;
                }
            }
        });
        // Also update title attribute translations
        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            el.title = this.t(el.getAttribute('data-i18n-title'));
        });
    },

    /** Update toggle button state */
    updateToggle() {
        document.querySelectorAll('.lang-toggle').forEach(toggle => {
            const frBtn = toggle.querySelector('.lang-fr');
            const enBtn = toggle.querySelector('.lang-en');
            if (frBtn && enBtn) {
                frBtn.classList.toggle('active', this.currentLang === 'fr');
                enBtn.classList.toggle('active', this.currentLang === 'en');
            }
        });
    },

    /** Create the toggle HTML */
    createToggleHTML() {
        const frActive = this.currentLang === 'fr' ? 'active' : '';
        const enActive = this.currentLang === 'en' ? 'active' : '';
        return `
        <div class="lang-toggle" role="group" aria-label="Language">
            <button class="lang-btn lang-fr ${frActive}" onclick="I18N.setLang('fr')" title="Français">
                <span class="lang-flag">🇫🇷</span><span class="lang-label">FR</span>
            </button>
            <button class="lang-btn lang-en ${enActive}" onclick="I18N.setLang('en')" title="English">
                <span class="lang-flag">🇬🇧</span><span class="lang-label">EN</span>
            </button>
        </div>`;
    },

    /** Initialize — call on DOMContentLoaded */
    init() {
        document.documentElement.lang = this.currentLang;
        // Inject toggles
        document.querySelectorAll('.lang-toggle-mount').forEach(mount => {
            mount.innerHTML = this.createToggleHTML();
        });
        // Apply saved language
        if (this.currentLang !== 'fr') {
            this.applyTranslations();
        }
        this.updateToggle();
    }
};

// Auto-init when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => I18N.init());
} else {
    I18N.init();
}
