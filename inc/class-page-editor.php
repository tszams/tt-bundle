<?php
if (!defined('ABSPATH')) {
    exit;
}

class TaxiTheme_Page_Editor {

    const PAGE_SLUG    = 'taxitheme-page-edit';
    const NONCE_ACTION = 'taxitheme_page_edit_save';
    const NONCE_FIELD  = 'taxitheme_page_edit_nonce';

    private static $saved = false;

    public static function init() {
        add_action('admin_menu',       [__CLASS__, 'register_hidden_page']);
        add_action('admin_init',       [__CLASS__, 'handle_submit']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_styles']);

        add_filter('page_row_actions', [__CLASS__, 'row_actions'], 10, 2);
        add_filter('get_edit_post_link', [__CLASS__, 'override_edit_link'], 10, 2);
    }

    public static function enqueue_styles($hook) {
        // Hook-suffix voor hidden pages is 'admin_page_{slug}'.
        // Check ook expliciet op $_GET['page'] — sommige situaties (redirect, POST) hebben andere hook-values.
        $on_editor = ($hook === 'admin_page_' . self::PAGE_SLUG)
                  || (isset($_GET['page']) && $_GET['page'] === self::PAGE_SLUG);
        if (!$on_editor) {
            return;
        }
        wp_enqueue_style(
            'taxitheme-editor-font',
            'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
            [],
            null
        );
        // Media library — voor image-upload velden (hero image, features).
        wp_enqueue_media();
    }

    /**
     * Render een image-upload veld met WP media library integratie.
     * Verwacht dat wp_enqueue_media() is aangeroepen (zie enqueue_styles).
     * Sla het attachment-ID op als hidden input; JS onderaan de pagina handelt de picker af.
     */
    public static function render_image_field($name, $attachment_id, $label = '') {
        $attachment_id = (int) $attachment_id;
        $preview_url   = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';
        $has_image     = $attachment_id && $preview_url;
        ?>
        <div class="tt-ed__field tt-ed__field--image tt-ed__image-field <?php echo $has_image ? 'has-image' : ''; ?>">
            <?php if ($label) : ?><label><?php echo esc_html($label); ?></label><?php endif; ?>
            <div class="tt-ed__image-preview">
                <?php if ($has_image) : ?>
                    <img src="<?php echo esc_url($preview_url); ?>" alt="">
                <?php else : ?>
                    <div class="tt-ed__image-placeholder">Nog geen afbeelding</div>
                <?php endif; ?>
            </div>
            <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($attachment_id); ?>">
            <div class="tt-ed__image-actions">
                <button type="button" class="tt-ed__btn tt-ed__btn--ghost tt-ed__image-select">Kies afbeelding</button>
                <button type="button" class="tt-ed__btn tt-ed__btn--ghost tt-ed__image-clear" <?php echo $has_image ? '' : 'style="display:none;"'; ?>>Verwijderen</button>
            </div>
        </div>
        <?php
    }

    /**
     * Inline JS voor image-picker. Roept 'm 1x aan onderaan de editor pagina.
     */
    public static function render_image_picker_js() {
        ?>
        <script>
        (function () {
            // Click-handler koppelen ongeacht wp.media status.
            // wp.media wordt door WordPress in de admin-footer geladen, dus check pas bij klik.
            document.addEventListener('click', function (e) {
                var selectBtn = e.target.closest('.tt-ed__image-select');
                if (selectBtn) {
                    e.preventDefault();
                    if (typeof wp === 'undefined' || !wp.media) {
                        console.warn('[TaxiTheme] wp.media niet geladen — is wp_enqueue_media() aangeroepen?');
                        return;
                    }
                    var field    = selectBtn.closest('.tt-ed__image-field');
                    var input    = field.querySelector('input[type=hidden]');
                    var preview  = field.querySelector('.tt-ed__image-preview');
                    var clearBtn = field.querySelector('.tt-ed__image-clear');
                    var frame = wp.media({
                        title: 'Kies afbeelding',
                        button: { text: 'Gebruiken' },
                        multiple: false,
                        library: { type: 'image' }
                    });
                    frame.on('select', function () {
                        var att = frame.state().get('selection').first().toJSON();
                        var url = (att.sizes && att.sizes.medium && att.sizes.medium.url) || att.url;
                        input.value = att.id;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        preview.innerHTML = '<img src="' + url + '" alt="">';
                        field.classList.add('has-image');
                        if (clearBtn) clearBtn.style.display = '';
                    });
                    frame.open();
                    return;
                }
                var removeBtn = e.target.closest('.tt-ed__image-clear');
                if (removeBtn) {
                    e.preventDefault();
                    var f = removeBtn.closest('.tt-ed__image-field');
                    var hiddenInput = f.querySelector('input[type=hidden]');
                    hiddenInput.value = '';
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    f.querySelector('.tt-ed__image-preview').innerHTML =
                        '<div class="tt-ed__image-placeholder">Nog geen afbeelding</div>';
                    f.classList.remove('has-image');
                    removeBtn.style.display = 'none';
                }
            });

            // ============ Dirty state: save bar + beforeunload warning ============
            //
            // Bar is verborgen totdat user iets aanpast in de form. Zodra dirty:
            //  - bar slidet in
            //  - "wijzigingen niet opgeslagen" waarschuwing bij navigate away
            //  - groene "opgeslagen" pill (na save) verdwijnt op eerste change of na 3s
            (function () {
                var form = document.querySelector('.tt-ed form');
                if (!form) return;
                var bar = form.querySelector('.tt-ed__actions');
                var isDirty = false;
                var isSubmitting = false;

                // 1. Verberg bar initieel — alleen tonen als dirty of als er een saved-pill is
                var hasSavedPill = form.querySelector('.tt-ed__saved-indicator');
                if (bar && !hasSavedPill) {
                    bar.classList.add('tt-ed__actions--hidden');
                }

                function markDirty() {
                    if (isDirty) return;
                    isDirty = true;
                    if (bar) bar.classList.remove('tt-ed__actions--hidden');
                    // Verwijder groene pill zodra user iets wijzigt
                    var pill = form.querySelector('.tt-ed__saved-indicator');
                    if (pill) {
                        pill.style.transition = 'opacity 0.2s, transform 0.2s';
                        pill.style.opacity = '0';
                        pill.style.transform = 'translateY(4px)';
                        setTimeout(function () { pill.remove(); }, 200);
                    }
                }

                // Auto-hide de saved pill na 3s (ook als niks aangepast)
                if (hasSavedPill) {
                    setTimeout(function () {
                        var pill = form.querySelector('.tt-ed__saved-indicator');
                        if (!pill || isDirty) return;
                        pill.style.transition = 'opacity 0.3s, transform 0.3s';
                        pill.style.opacity = '0';
                        pill.style.transform = 'translateY(4px)';
                        setTimeout(function () {
                            if (pill.parentNode) pill.remove();
                            if (bar && !isDirty) bar.classList.add('tt-ed__actions--hidden');
                        }, 300);
                    }, 3000);
                }

                // 2. Detect changes op alle form inputs
                form.addEventListener('input', markDirty);
                form.addEventListener('change', markDirty);

                // De visuele WordPress-editor wijzigt zijn verborgen textarea niet
                // bij iedere toetsaanslag. Luister daarom ook direct naar TinyMCE.
                if (window.jQuery) {
                    window.jQuery(document).on('tinymce-editor-init.taxitheme', function (event, editor) {
                        if (!editor || !editor.getElement || !form.contains(editor.getElement())) return;
                        editor.on('change input undo redo', markDirty);
                    });
                }

                // Sidebar reorder / toggle klikken tellen ook als wijziging
                var sidebar = document.getElementById('tt-ed-sidebar');
                if (sidebar) {
                    sidebar.addEventListener('click', function (e) {
                        if (e.target.closest('.tt-ed__sidebar-btn') || e.target.closest('.tt-ed__sidebar-switch')) {
                            markDirty();
                        }
                    });
                }

                // 3. beforeunload warning wanneer dirty en niet aan het submitten
                form.addEventListener('submit', function () { isSubmitting = true; });
                window.addEventListener('beforeunload', function (e) {
                    if (isDirty && !isSubmitting) {
                        e.preventDefault();
                        e.returnValue = '';
                        return '';
                    }
                });
            })();

            // ============ Left sidebar: sync + reorder ============
            //
            // 1. Elke .tt-ed__group[data-section-key] krijgt op load een id
            //    (tt-ed-section-{key}) zodat de jump-links werken.
            // 2. Sidebar toggle proxy klikt de bijbehorende panel-checkbox.
            //    De echte form input blijft in het panel — sidebar is visual only.
            // 3. ↑↓ knoppen swappen zowel de sidebar-row als de panel-group,
            //    zodat de hidden home[section_order][] inputs in de rows meebewegen
            //    en de panels visueel op de goede plek staan.

            (function () {
                var sidebar = document.getElementById('tt-ed-sidebar');
                if (!sidebar) return;

                // 1. Assign ids to section groups voor jump-scroll
                document.querySelectorAll('.tt-ed__group[data-section-key]').forEach(function (g) {
                    var key = g.getAttribute('data-section-key');
                    g.id = 'tt-ed-section-' + key;
                });

                // Achtergrondkeuze per component. De inputs worden hier
                // opgebouwd zodat alle bestaande panels automatisch meedoen.
                var backgroundConfigNode = document.getElementById('tt-ed-background-config');
                if (backgroundConfigNode) {
                    var backgroundConfig = null;
                    try { backgroundConfig = JSON.parse(backgroundConfigNode.getAttribute('data-config') || '{}'); }
                    catch (error) { backgroundConfig = null; }

                    if (backgroundConfig && backgroundConfig.enabled && Array.isArray(backgroundConfig.options)) {
                        document.querySelectorAll('.tt-ed__group[data-section-key]').forEach(function (group) {
                            var key = group.getAttribute('data-section-key');
                            var head = group.querySelector(':scope > .tt-ed__group-head');
                            if (!key || !head) return;

                            var selected = backgroundConfig.values && backgroundConfig.values[key]
                                ? backgroundConfig.values[key]
                                : 'auto';
                            var setting = document.createElement('div');
                            setting.className = 'tt-ed__background-setting';

                            var intro = document.createElement('div');
                            intro.className = 'tt-ed__background-intro';
                            intro.innerHTML = '<strong>Achtergrond</strong><span>Tekst en randen passen automatisch mee aan.</span>';
                            setting.appendChild(intro);

                            var choices = document.createElement('div');
                            choices.className = 'tt-ed__background-choices';
                            backgroundConfig.options.forEach(function (option, index) {
                                var label = document.createElement('label');
                                label.className = 'tt-ed__background-choice';

                                var radio = document.createElement('input');
                                radio.type = 'radio';
                                radio.name = 'home[section_backgrounds][' + backgroundConfig.preset + '][' + key + ']';
                                radio.value = option.value;
                                radio.checked = selected === option.value;
                                radio.id = 'tt-ed-bg-' + key + '-' + index;

                                var swatch = document.createElement('span');
                                swatch.className = 'tt-ed__background-swatch';
                                swatch.style.background = option.preview;

                                var text = document.createElement('span');
                                text.className = 'tt-ed__background-label';
                                text.textContent = option.label;

                                label.appendChild(radio);
                                label.appendChild(swatch);
                                label.appendChild(text);
                                choices.appendChild(label);
                            });
                            setting.appendChild(choices);
                            head.insertAdjacentElement('afterend', setting);

                            var fallback = backgroundConfigNode.querySelector(
                                '.tt-ed__background-fallback[data-preset="' + backgroundConfig.preset + '"][data-section-key="' + key + '"]'
                            );
                            if (fallback) fallback.remove();
                        });
                    }
                }

                // 2. Sync sidebar switch met panel checkbox (bidirectioneel)
                function findPanelToggle(row) {
                    var name = row.getAttribute('data-toggle-name');
                    if (!name) return null;
                    // querySelector met attribute selector — escape brackets
                    return document.querySelector('input[type="checkbox"][name="' + name + '"]');
                }
                sidebar.querySelectorAll('.tt-ed__sidebar-row').forEach(function (row) {
                    var toggleBtn = row.querySelector('.tt-ed__sidebar-switch');
                    if (!toggleBtn) return;
                    var panelInput = findPanelToggle(row);
                    if (!panelInput) return;

                    // Sync initial state (panel is de bron van waarheid)
                    toggleBtn.classList.toggle('is-on', panelInput.checked);
                    toggleBtn.setAttribute('aria-pressed', panelInput.checked ? 'true' : 'false');
                    row.classList.toggle('is-enabled', panelInput.checked);

                    toggleBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        panelInput.checked = !panelInput.checked;
                        panelInput.dispatchEvent(new Event('change', { bubbles: true }));
                        toggleBtn.classList.toggle('is-on', panelInput.checked);
                        toggleBtn.setAttribute('aria-pressed', panelInput.checked ? 'true' : 'false');
                        row.classList.toggle('is-enabled', panelInput.checked);
                    });

                    // Als panel input verandert (bv door user direct), sync sidebar
                    panelInput.addEventListener('change', function () {
                        toggleBtn.classList.toggle('is-on', panelInput.checked);
                        toggleBtn.setAttribute('aria-pressed', panelInput.checked ? 'true' : 'false');
                        row.classList.toggle('is-enabled', panelInput.checked);
                    });
                });

                // 3. Up/down knoppen — swap sidebar row én panel group
                sidebar.addEventListener('click', function (e) {
                    var btn = e.target.closest('.tt-ed__sidebar-btn');
                    if (!btn) return;
                    e.preventDefault();
                    var row = btn.closest('.tt-ed__sidebar-row');
                    if (!row) return;
                    var dir = btn.getAttribute('data-dir');
                    var key = row.getAttribute('data-section-key');
                    var group = document.getElementById('tt-ed-section-' + key);

                    if (dir === 'up' && row.previousElementSibling) {
                        row.parentNode.insertBefore(row, row.previousElementSibling);
                        if (group) {
                            // Vind vorige reorderable group
                            var prev = group.previousElementSibling;
                            while (prev && !(prev.classList.contains('tt-ed__group') && prev.hasAttribute('data-section-key'))) {
                                prev = prev.previousElementSibling;
                            }
                            if (prev) group.parentNode.insertBefore(group, prev);
                        }
                    } else if (dir === 'down' && row.nextElementSibling) {
                        row.parentNode.insertBefore(row.nextElementSibling, row);
                        if (group) {
                            var next = group.nextElementSibling;
                            while (next && !(next.classList.contains('tt-ed__group') && next.hasAttribute('data-section-key'))) {
                                next = next.nextElementSibling;
                            }
                            if (next) group.parentNode.insertBefore(next, group);
                        }
                    }

                    if (group) {
                        group.classList.add('tt-ed__group--moved');
                        setTimeout(function () { group.classList.remove('tt-ed__group--moved'); }, 500);
                    }
                });

                // 4. Sidebar collapse toggle
                var collapseBtn = sidebar.querySelector('.tt-ed__sidebar-toggle');
                if (collapseBtn) {
                    collapseBtn.addEventListener('click', function () {
                        var collapsed = sidebar.classList.toggle('is-collapsed');
                        collapseBtn.textContent = collapsed ? '+' : '−';
                        collapseBtn.title = collapsed ? 'Uitklappen' : 'Inklappen';
                        collapseBtn.setAttribute('aria-label', collapsed ? 'Navigatie uitklappen' : 'Navigatie inklappen');
                        collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    });
                }

            })();

            // ============ Componentweergave ============
            // De editor toont steeds één hoofdonderdeel. De sidebar (homepage) of
            // compacte navigatie (subpagina's) wisselt tussen de onderdelen.
            (function () {
                var form = document.querySelector('.tt-ed form');
                if (!form) return;

                var groups = Array.prototype.slice.call(form.querySelectorAll('.tt-ed__group'));
                if (!groups.length) return;

                var postInput = form.querySelector('input[name="post"]');
                var storageKey = 'taxitheme-editor-' + (postInput ? postInput.value : 'page') + '-active-view';
                var isHomeEditor = !!form.querySelector('.tt-ed__layout');
                var componentConfig = null;
                if (!isHomeEditor && form.getAttribute('data-component-config')) {
                    try { componentConfig = JSON.parse(form.getAttribute('data-component-config')); }
                    catch (error) { componentConfig = null; }
                }
                var definitionKeys = componentConfig ? Object.keys(componentConfig.definitions || {}) : [];

                function slugify(value) {
                    return value.toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-|-$/g, '') || 'onderdeel';
                }

                function readActiveView() {
                    try { return window.localStorage.getItem(storageKey); }
                    catch (error) { return null; }
                }

                function saveActiveView(id) {
                    try { window.localStorage.setItem(storageKey, id); }
                    catch (error) { /* localStorage kan door browserbeleid geblokkeerd zijn. */ }
                }

                groups.forEach(function (group, index) {
                    var head = group.querySelector(':scope > .tt-ed__group-head');
                    if (!head) return;

                    var heading = head.querySelector('h3');
                    var fallbackLabel = heading ? heading.textContent.trim() : 'Onderdeel ' + (index + 1);
                    var existingId = group.id || '';
                    var configuredKey = definitionKeys[index] || '';
                    var editorId = group.getAttribute('data-section-key') || configuredKey || slugify(fallbackLabel) + '-' + index;
                    var navLabel = componentConfig && componentConfig.definitions[editorId]
                        ? componentConfig.definitions[editorId]
                        : fallbackLabel;

                    group.setAttribute('data-editor-id', editorId);
                    group.setAttribute('data-nav-label', navLabel);
                    if (!existingId) group.id = 'tt-ed-group-' + editorId;
                    group.classList.add('tt-ed__component-view');
                });

                if (componentConfig && Array.isArray(componentConfig.order)) {
                    groups.sort(function (a, b) {
                        var aIndex = componentConfig.order.indexOf(a.getAttribute('data-editor-id'));
                        var bIndex = componentConfig.order.indexOf(b.getAttribute('data-editor-id'));
                        return (aIndex < 0 ? 999 : aIndex) - (bIndex < 0 ? 999 : bIndex);
                    });
                }

                // Subpagina's krijgen dezelfde linkerzijbalk als de homepage.
                if (!isHomeEditor) {
                    var layout = document.createElement('div');
                    layout.className = 'tt-ed__layout tt-ed__layout--subpage';

                    var sidebar = document.createElement('aside');
                    sidebar.className = 'tt-ed__sidebar tt-ed__sidebar--subpage';
                    sidebar.setAttribute('aria-label', 'Onderdelen van deze pagina');

                    var sidebarHead = document.createElement('div');
                    sidebarHead.className = 'tt-ed__sidebar-head';
                    sidebarHead.innerHTML = '<strong>Paginaonderdelen</strong>' +
                        '<button type="button" class="tt-ed__sidebar-toggle" title="Navigatie inklappen" aria-label="Navigatie inklappen" aria-expanded="true">−</button>';
                    sidebar.appendChild(sidebarHead);

                    var sectionLabel = document.createElement('div');
                    sectionLabel.className = 'tt-ed__sidebar-section-label';
                    sectionLabel.textContent = 'Inhoud';
                    sidebar.appendChild(sectionLabel);

                    var configPresent = document.createElement('input');
                    configPresent.type = 'hidden';
                    configPresent.name = 'page[component_config_present]';
                    configPresent.value = '1';
                    sidebar.appendChild(configPresent);

                    var links = document.createElement('ol');
                    links.className = 'tt-ed__sidebar-list';
                    groups.forEach(function (group) {
                        var key = group.getAttribute('data-editor-id');
                        var label = group.getAttribute('data-nav-label');
                        var enabled = !componentConfig || !componentConfig.enabled || componentConfig.enabled[key] !== false;

                        var row = document.createElement('li');
                        row.className = 'tt-ed__sidebar-row' + (enabled ? ' is-enabled' : '');
                        row.setAttribute('data-component-key', key);

                        var link = document.createElement('a');
                        link.href = '#' + group.id;
                        link.className = 'tt-ed__sidebar-jump';
                        link.textContent = label;
                        row.appendChild(link);

                        var up = document.createElement('button');
                        up.type = 'button';
                        up.className = 'tt-ed__sidebar-btn';
                        up.setAttribute('data-dir', 'up');
                        up.setAttribute('aria-label', label + ' omhoog verplaatsen');
                        up.textContent = '↑';
                        row.appendChild(up);

                        var down = document.createElement('button');
                        down.type = 'button';
                        down.className = 'tt-ed__sidebar-btn';
                        down.setAttribute('data-dir', 'down');
                        down.setAttribute('aria-label', label + ' omlaag verplaatsen');
                        down.textContent = '↓';
                        row.appendChild(down);

                        var toggle = document.createElement('button');
                        toggle.type = 'button';
                        toggle.className = 'tt-ed__sidebar-switch' + (enabled ? ' is-on' : '');
                        toggle.setAttribute('aria-label', label + ' tonen of verbergen');
                        toggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
                        toggle.innerHTML = '<span class="tt-ed__sidebar-switch-track"><span class="tt-ed__sidebar-switch-thumb"></span></span>';
                        row.appendChild(toggle);

                        var enabledInput = document.createElement('input');
                        enabledInput.type = 'checkbox';
                        enabledInput.className = 'tt-ed__sidebar-native-toggle';
                        enabledInput.name = 'page[component_enabled][' + key + ']';
                        enabledInput.value = '1';
                        enabledInput.checked = enabled;
                        row.appendChild(enabledInput);

                        var orderInput = document.createElement('input');
                        orderInput.type = 'hidden';
                        orderInput.name = 'page[component_order][]';
                        orderInput.value = key;
                        row.appendChild(orderInput);

                        links.appendChild(row);
                    });
                    sidebar.appendChild(links);

                    var help = document.createElement('p');
                    help.className = 'tt-ed__sidebar-help';
                    help.textContent = 'Klik om te bewerken. Gebruik de pijlen voor de volgorde en de schakelaar voor tonen of verbergen.';
                    sidebar.appendChild(help);

                    var main = document.createElement('div');
                    main.className = 'tt-ed__main';

                    var firstGroup = form.querySelector('.tt-ed__group');
                    if (firstGroup) {
                        form.insertBefore(layout, firstGroup);
                        layout.appendChild(sidebar);
                        layout.appendChild(main);
                        groups.forEach(function (group) { main.appendChild(group); });
                    }

                    var sidebarToggle = sidebar.querySelector('.tt-ed__sidebar-toggle');
                    sidebarToggle.addEventListener('click', function () {
                        var collapsed = sidebar.classList.toggle('is-collapsed');
                        sidebarToggle.textContent = collapsed ? '+' : '−';
                        sidebarToggle.title = collapsed ? 'Navigatie uitklappen' : 'Navigatie inklappen';
                        sidebarToggle.setAttribute('aria-label', sidebarToggle.title);
                        sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    });

                    links.addEventListener('click', function (event) {
                        var moveButton = event.target.closest('.tt-ed__sidebar-btn');
                        if (moveButton) {
                            event.preventDefault();
                            var row = moveButton.closest('.tt-ed__sidebar-row');
                            var key = row.getAttribute('data-component-key');
                            var group = form.querySelector('.tt-ed__component-view[data-editor-id="' + key + '"]');
                            var direction = moveButton.getAttribute('data-dir');
                            if (direction === 'up' && row.previousElementSibling) {
                                links.insertBefore(row, row.previousElementSibling);
                                if (group && group.previousElementSibling) group.parentNode.insertBefore(group, group.previousElementSibling);
                            } else if (direction === 'down' && row.nextElementSibling) {
                                links.insertBefore(row.nextElementSibling, row);
                                if (group && group.nextElementSibling) group.parentNode.insertBefore(group.nextElementSibling, group);
                            }
                            var orderInput = row.querySelector('input[type="hidden"]');
                            if (orderInput) orderInput.dispatchEvent(new Event('change', { bubbles: true }));
                            return;
                        }

                        var toggleButton = event.target.closest('.tt-ed__sidebar-switch');
                        if (toggleButton) {
                            event.preventDefault();
                            var toggleRow = toggleButton.closest('.tt-ed__sidebar-row');
                            var checkbox = toggleRow.querySelector('.tt-ed__sidebar-native-toggle');
                            checkbox.checked = !checkbox.checked;
                            toggleButton.classList.toggle('is-on', checkbox.checked);
                            toggleButton.setAttribute('aria-pressed', checkbox.checked ? 'true' : 'false');
                            toggleRow.classList.toggle('is-enabled', checkbox.checked);
                            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                }

                function selectView(group) {
                    if (!group || groups.indexOf(group) === -1) return;

                    groups.forEach(function (item) {
                        var active = item === group;
                        item.classList.toggle('is-active-view', active);
                        item.setAttribute('aria-hidden', active ? 'false' : 'true');
                    });

                    form.querySelectorAll('.tt-ed__sidebar-jump').forEach(function (link) {
                        var active = link.getAttribute('href') === '#' + group.id;
                        link.classList.toggle('is-active', active);
                        if (active) {
                            link.setAttribute('aria-current', 'true');
                        } else {
                            link.removeAttribute('aria-current');
                        }
                        var row = link.closest('.tt-ed__sidebar-row');
                        if (row) row.classList.toggle('is-active-component', active);
                    });

                    saveActiveView(group.id);

                    // Editors die in een verborgen component zijn geïnitialiseerd
                    // opnieuw laten meten zodra hun component zichtbaar wordt.
                    window.requestAnimationFrame(function () {
                        window.dispatchEvent(new Event('resize'));
                        if (!window.tinymce || !window.tinymce.editors) return;
                        window.tinymce.editors.forEach(function (editor) {
                            if (!editor || !editor.getElement || !group.contains(editor.getElement())) return;
                            try { editor.fire('ResizeEditor'); } catch (error) { /* Geen resize-hook beschikbaar. */ }
                        });
                    });

                }

                form.addEventListener('click', function (event) {
                    var link = event.target.closest('.tt-ed__sidebar-jump');
                    if (!link || !form.contains(link)) return;
                    var href = link.getAttribute('href');
                    if (!href || href.charAt(0) !== '#') return;
                    var group = document.getElementById(href.substring(1));
                    if (!group) return;
                    event.preventDefault();
                    selectView(group);
                });

                form.classList.add('tt-ed__form--component-mode');

                var initialGroup = null;
                if (window.location.hash) {
                    initialGroup = document.getElementById(window.location.hash.substring(1));
                }
                if (groups.indexOf(initialGroup) === -1) {
                    initialGroup = null;
                    var storedId = readActiveView();
                    if (storedId) initialGroup = document.getElementById(storedId);
                }
                if (groups.indexOf(initialGroup) === -1) initialGroup = null;
                selectView(initialGroup || groups[0]);
            })();
        })();
        </script>
        <?php
    }

    /**
     * No-op sinds we naar de sidebar zijn overgestapt.
     * De hidden order inputs + up/down buttons zitten nu in de left sidebar
     * (zie render_home_editor). Het data-section-key attribuut op elke
     * .tt-ed__group blijft nodig zodat JS de panels kan swappen.
     */
    public static function render_section_order_controls($key) {
        // Intentionally empty.
    }

    /**
     * Simple editor voor privacy/voorwaarden — titel + TinyMCE content.
     * De klant bewerkt direct de WP post_title en post_content via een familiar UI.
     */
    private static function render_legal_page_editor($post_id, $role) {
        $post = get_post($post_id);
        if (!$post) return;
        $component_config = TaxiTheme_Page_Meta::get_component_config($post_id, $role);
        ?>
        <form method="post" data-component-config="<?php echo esc_attr(wp_json_encode($component_config)); ?>">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
            <input type="hidden" name="post" value="<?php echo (int) $post_id; ?>">
            <input type="hidden" name="taxitheme_legal_edit" value="1">

            <div class="tt-ed__group">
                <div class="tt-ed__group-head">
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('edit', 20); ?> Paginatitel</h3>
                        <p>De titel bovenaan de pagina (ook gebruikt in de browser-tab en zoekresultaten).</p>
                    </div>
                </div>
                <div class="tt-ed__field tt-ed__field--full">
                    <input type="text" name="legal[title]" value="<?php echo esc_attr($post->post_title); ?>" placeholder="Bijv. Privacybeleid">
                </div>
            </div>

            <div class="tt-ed__group">
                <div class="tt-ed__group-head">
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('sparkles', 20); ?> Inhoud</h3>
                        <p>De volledige tekst van de pagina. Gebruik de knoppen om koppen, alinea's, lijsten en links toe te voegen.</p>
                    </div>
                </div>
                <div class="tt-ed__field tt-ed__field--full tt-ed__wysiwyg-wrap">
                    <?php
                    wp_editor($post->post_content, 'legal_content', [
                        'textarea_name' => 'legal[content]',
                        'textarea_rows' => 20,
                        'media_buttons' => true,
                        'teeny'         => false,
                    ]);
                    ?>
                </div>
            </div>

            <div class="tt-ed__actions">
                <button type="submit" class="tt-ed__btn tt-ed__btn--primary">Wijzigingen opslaan</button>
                <?php if (self::$saved) : ?>
                    <span class="tt-ed__saved-indicator" data-auto-hide="1">✓ Wijzigingen opgeslagen</span>
                <?php endif; ?>
            </div>
        </form>
        <?php
        self::render_image_picker_js();
    }

    public static function register_hidden_page() {
        add_submenu_page(
            null,  // hidden — geen menu item
            'Bewerk TaxiTheme sectie',
            'Bewerk TaxiTheme sectie',
            'manage_options',
            self::PAGE_SLUG,
            [__CLASS__, 'render']
        );
    }

    /**
     * Replace standard Edit URL in WP Pages list for TaxiTheme pages that have a section editor.
     */
    public static function row_actions($actions, $post) {
        if ($post->post_type !== 'page') return $actions;
        $role = get_post_meta($post->ID, TaxiTheme_Installer::META_ROLE, true);
        if (!$role || !self::role_has_editor($role)) return $actions;

        $url = self::edit_url($post->ID);
        if (isset($actions['edit'])) {
            $actions['edit'] = '<a href="' . esc_url($url) . '">Bewerken</a>';
        }
        // Verwijder "Quick Edit" en block-editor variant om verwarring te voorkomen
        unset($actions['inline hide-if-no-js']);
        return $actions;
    }

    /**
     * Redirect standard Edit link for TaxiTheme pages to our custom editor.
     */
    public static function override_edit_link($link, $post_id) {
        if (!is_admin()) return $link;

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'edit-page') return $link;

        $role = get_post_meta($post_id, TaxiTheme_Installer::META_ROLE, true);
        if (!$role || !self::role_has_editor($role)) return $link;

        return self::edit_url($post_id);
    }

    private static function edit_url($post_id) {
        return admin_url('admin.php?page=' . self::PAGE_SLUG . '&post=' . (int) $post_id);
    }

    const ROLES_WITH_META_EDITOR = ['tarieven', 'diensten', 'over-ons', 'contact', 'faq', 'privacy', 'voorwaarden'];

    public static function role_has_editor($role) {
        if ($role === 'home') return true;
        return in_array($role, self::ROLES_WITH_META_EDITOR, true);
    }

    public static function edit_url_for_page($post_id) {
        return self::edit_url($post_id);
    }

    public static function handle_submit() {
        if (empty($_POST[self::NONCE_FIELD])) return;
        if (!current_user_can('manage_options')) return;
        if (!wp_verify_nonce($_POST[self::NONCE_FIELD], self::NONCE_ACTION)) wp_die('Beveiligingscheck mislukt.');

        $post_id = (int) ($_POST['post'] ?? 0);
        if (!$post_id) return;

        $role = get_post_meta($post_id, TaxiTheme_Installer::META_ROLE, true);
        if (!$role) return;

        $action = $_POST['taxitheme_action'] ?? 'save';

        if ($role === 'home') {
            if ($action === 'reset') {
                TaxiTheme_Home_Content::reset();
            } else {
                TaxiTheme_Home_Content::save($_POST['home'] ?? []);
            }
            self::$saved = true;
            return;
        }

        if (in_array($role, self::ROLES_WITH_META_EDITOR, true)) {

            // Privacy / Voorwaarden: aparte save-flow voor titel + post_content.
            // De andere page-meta velden (intro, sections, CTA override) worden overgeslagen.
            if (in_array($role, ['privacy', 'voorwaarden'], true) && isset($_POST['taxitheme_legal_edit'])) {
                $legal = $_POST['legal'] ?? [];
                if (is_array($legal)) {
                    $updates = ['ID' => $post_id];
                    if (isset($legal['title'])) {
                        $new_title = sanitize_text_field(wp_unslash($legal['title']));
                        if ($new_title !== '') $updates['post_title'] = $new_title;
                    }
                    if (isset($legal['content'])) {
                        // WYSIWYG output — laat wp_kses_post door zodat toegestane HTML behouden blijft
                        $updates['post_content'] = wp_kses_post(wp_unslash($legal['content']));
                    }
                    if (count($updates) > 1) {
                        wp_update_post($updates);
                    }
                }
                TaxiTheme_Page_Meta::save_component_config($post_id, $_POST['page'] ?? []);
                self::$saved = true;
                return;
            }

            TaxiTheme_Page_Meta::save($post_id, $_POST['page'] ?? []);

            // Over ons: extra hero-velden + footer CTA + 4 USPs
            if ($role === 'over-ons' && isset($_POST['over_ons']) && is_array($_POST['over_ons'])) {
                TaxiTheme_Page_Meta::save_over_ons_extras($post_id, $_POST['over_ons']);
            }

            // Diensten-page: merge de per-service detail-velden (image, long_text, features, price)
            // in bestaande services_items zonder icon/title/text/link te overschrijven.
            // Loopt door 6 slots — als slot bestaat mergen, anders nieuwe entry met defaults.
            if ($role === 'diensten' && isset($_POST['page_extra']['services_items'])) {
                $home     = TaxiTheme_Home_Content::all();
                $incoming = $_POST['page_extra']['services_items'];
                $empty_svc = ['icon' => 'car', 'title' => '', 'text' => '', 'link_url' => '', 'link_label' => '', 'home_image_id' => 0, 'image_id' => 0, 'long_text' => '', 'features' => '', 'price' => ''];
                for ($i = 0; $i < 6; $i++) {
                    if (!isset($home['services_items'][$i])) {
                        $home['services_items'][$i] = $empty_svc;
                    }
                    if (!isset($incoming[$i])) continue;
                    $home['services_items'][$i]['image_id']  = (int) ($incoming[$i]['image_id'] ?? 0);
                    $home['services_items'][$i]['long_text'] = wp_unslash($incoming[$i]['long_text'] ?? '');
                    $home['services_items'][$i]['features']  = wp_unslash($incoming[$i]['features']  ?? '');
                    $home['services_items'][$i]['price']     = wp_unslash($incoming[$i]['price']     ?? '');
                }
                TaxiTheme_Home_Content::save($home);
            }

            // FAQ-page: save de extra vragen in een APARTE array (faq_page_items).
            // Deze items renderen alleen op de FAQ-pagina — niet op home. 15 slots.
            if ($role === 'faq' && isset($_POST['page_extra']['faq_page_items'])) {
                $home     = TaxiTheme_Home_Content::all();
                $incoming = $_POST['page_extra']['faq_page_items'];
                for ($i = 0; $i < 15; $i++) {
                    if (!isset($home['faq_page_items'][$i])) {
                        $home['faq_page_items'][$i] = ['question' => '', 'answer' => ''];
                    }
                    if (!isset($incoming[$i])) continue;
                    $home['faq_page_items'][$i]['question'] = wp_unslash($incoming[$i]['question'] ?? '');
                    $home['faq_page_items'][$i]['answer']   = wp_unslash($incoming[$i]['answer']   ?? '');
                }
                TaxiTheme_Home_Content::save($home);
            }

            self::$saved = true;
        }
    }

    public static function render() {
        $post_id = (int) ($_GET['post'] ?? 0);
        $post    = $post_id ? get_post($post_id) : null;
        if (!$post || $post->post_type !== 'page') {
            echo '<div class="wrap"><p>Ongeldige pagina.</p></div>';
            return;
        }
        $role = get_post_meta($post_id, TaxiTheme_Installer::META_ROLE, true);
        if (!$role || !self::role_has_editor($role)) {
            echo '<div class="wrap"><p>Deze pagina heeft geen TaxiTheme sectie-editor.</p></div>';
            return;
        }

        self::render_styles();
        ?>
        <div class="tt-ed">
            <div class="tt-ed__topbar">
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . TaxiTheme_Setup::WIZARD_SLUG . '&tab=pages')); ?>" class="tt-ed__back">← Terug naar pagina's</a>
                <div class="tt-ed__title-block">
                    <h1 class="tt-ed__title"><?php echo esc_html($post->post_title); ?> bewerken</h1>
                    <p class="tt-ed__subtitle">Pas de inhoud per onderdeel aan. Je wijzigingen worden pas zichtbaar nadat je ze opslaat.</p>
                </div>
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" target="_blank" rel="noopener noreferrer" class="tt-ed__view">Pagina bekijken ↗</a>
            </div>


            <?php
            if ($role === 'home') {
                self::render_home_editor($post_id);
            } elseif (in_array($role, self::ROLES_WITH_META_EDITOR, true)) {
                self::render_page_meta_editor($post_id, $role);
            }
            ?>
        </div>
        <?php
    }

    /**
     * Simpele editor voor sub-pages (tarieven, diensten, over-ons, contact, faq).
     * Velden: intro-tekst + tot 3 extra info-blokken (title + text).
     * Shared data (routes, contact-CTA, services) blijft in de homepage-editor.
     */
    private static function render_page_meta_editor($post_id, $role) {
        // Privacy / Voorwaarden: simpele editor (titel + WYSIWYG voor lange prose).
        // Deze rollen hebben geen intro/sections/USPs — alleen de content zelf.
        if (in_array($role, ['privacy', 'voorwaarden'], true)) {
            self::render_legal_page_editor($post_id, $role);
            return;
        }

        // Over ons: eenmalig defaults inseeden zodat de klant een gevulde pagina ziet.
        if ($role === 'over-ons') {
            TaxiTheme_Page_Meta::ensure_over_ons_seeded($post_id);
        }
        // Tarieven: eenmalig voorbeelddata inseeden (klant kan direct de layout zien).
        if ($role === 'tarieven') {
            TaxiTheme_Page_Meta::ensure_tarieven_seeded($post_id);
        }

        $intro       = TaxiTheme_Page_Meta::get_intro($post_id);
        $sections    = TaxiTheme_Page_Meta::get_sections($post_id);
        $home_data   = TaxiTheme_Home_Content::all();
        $over_ons_extras = $role === 'over-ons' ? TaxiTheme_Page_Meta::get_over_ons_extras($post_id) : [];
        $over_ons_usps   = $role === 'over-ons' ? TaxiTheme_Page_Meta::get_over_ons_usps($post_id) : [];
        $icon_options = ['check', 'check-circle', 'shield', 'clock', 'phone', 'car', 'users', 'map-pin', 'calendar', 'star', 'info', 'euro', 'sparkles'];
        $component_config = TaxiTheme_Page_Meta::get_component_config($post_id, $role);
        ?>
        <form method="post" data-component-config="<?php echo esc_attr(wp_json_encode($component_config)); ?>">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
            <input type="hidden" name="post" value="<?php echo (int) $post_id; ?>">

            <div class="tt-ed__preset-note">
                <?php echo TaxiTheme_Icons::svg('info', 16); ?>
                Dit zijn de <strong>paginaspecifieke</strong> velden. Gedeelde secties (routes,
                contact-CTA, diensten) beheer je op de homepage-editor.
            </div>

            <?php if ($role === 'over-ons') :
                // Bewaar de oude over-ons velden (eyebrow, CTAs, footer CTA) via hidden
                // inputs zodat bestaande data niet weggegooid wordt. De editor UI voor
                // deze velden is verwijderd — over-ons gebruikt nu de shared CTA-banner. ?>
                <input type="hidden" name="over_ons[eyebrow]"          value="<?php echo esc_attr($over_ons_extras['eyebrow']); ?>">
                <input type="hidden" name="over_ons[cta1_label]"       value="<?php echo esc_attr($over_ons_extras['cta1_label']); ?>">
                <input type="hidden" name="over_ons[cta1_url]"         value="<?php echo esc_attr($over_ons_extras['cta1_url']);   ?>">
                <input type="hidden" name="over_ons[cta2_label]"       value="<?php echo esc_attr($over_ons_extras['cta2_label']); ?>">
                <input type="hidden" name="over_ons[cta2_url]"         value="<?php echo esc_attr($over_ons_extras['cta2_url']);   ?>">
                <input type="hidden" name="over_ons[footer_title]"     value="<?php echo esc_attr($over_ons_extras['footer_title']); ?>">
                <input type="hidden" name="over_ons[footer_text]"      value="<?php echo esc_attr($over_ons_extras['footer_text']); ?>">
                <input type="hidden" name="over_ons[footer_cta1_label]" value="<?php echo esc_attr($over_ons_extras['footer_cta1_label']); ?>">
                <input type="hidden" name="over_ons[footer_cta1_url]"   value="<?php echo esc_attr($over_ons_extras['footer_cta1_url']);   ?>">
                <input type="hidden" name="over_ons[footer_cta2_label]" value="<?php echo esc_attr($over_ons_extras['footer_cta2_label']); ?>">
                <input type="hidden" name="over_ons[footer_cta2_url]"   value="<?php echo esc_attr($over_ons_extras['footer_cta2_url']);   ?>">
            <?php endif; ?>

            <?php // Intro-tekst — verschijnt onder de page-titel bovenaan de sub-page.
                  // Optioneel: klant kan leeg laten voor titel-alleen header. ?>
            <div class="tt-ed__group">
                <div class="tt-ed__group-head">
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('edit', 20); ?> Intro-tekst <span class="tt-ed__hint" style="font-weight:400;font-size:0.85rem;color:#6b7280;">(optioneel)</span></h3>
                        <p>Verschijnt onder de paginatitel bovenaan de pagina. Laat leeg voor een rustige paginakop met alleen de titel.</p>
                    </div>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>Tekst <span class="tt-ed__hint">(lege regel = nieuwe paragraaf)</span></label>
                        <textarea name="page[intro]" rows="4"><?php echo esc_textarea($intro); ?></textarea>
                    </div>
                </div>
            </div>

            <?php if ($role === 'over-ons') : ?>
                <?php
                // Backward-compat: als usps_enabled nooit expliciet is opgeslagen (leeg
                // string), behandelen we het als aan. Alleen expliciet "0" = uit.
                $usps_enabled_val = $over_ons_extras['usps_enabled'] ?? '';
                $usps_enabled     = $usps_enabled_val !== '0';
                ?>
                <div class="tt-ed__group">
                    <div class="tt-ed__group-head">
                        <div class="tt-ed__group-head-text">
                            <h3><?php echo TaxiTheme_Icons::svg('check-circle', 20); ?> USP-kaartjes (max 4)</h3>
                            <p>Verschijnen als kaartjes onder de paginakop. Zet de schakelaar uit om deze hele rij te verbergen.</p>
                        </div>
                        <label class="tt-ed__toggle">
                            <input type="checkbox" name="over_ons[usps_enabled]" value="1" <?php checked($usps_enabled); ?>>
                            <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                        </label>
                    </div>
                    <div class="tt-ed__usps">
                        <?php foreach ($over_ons_usps as $i => $usp) : ?>
                            <div class="tt-ed__usp-row">
                                <div class="tt-ed__usp-num">USP <?php echo $i + 1; ?></div>
                                <div class="tt-ed__usp-grid">
                                    <div class="tt-ed__field">
                                        <label>Icoon</label>
                                        <select name="over_ons[usps][<?php echo $i; ?>][icon]">
                                            <option value="">— geen —</option>
                                            <?php foreach ($icon_options as $ico) : ?>
                                                <option value="<?php echo esc_attr($ico); ?>" <?php selected($usp['icon'], $ico); ?>><?php echo esc_html($ico); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="tt-ed__field">
                                        <label>Titel <span class="tt-ed__hint">(leeg = kaart wordt niet getoond)</span></label>
                                        <input type="text" name="over_ons[usps][<?php echo $i; ?>][title]" value="<?php echo esc_attr($usp['title']); ?>" placeholder="Bijv. Duidelijkheid vooraf">
                                    </div>
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Tekst</label>
                                        <textarea name="over_ons[usps][<?php echo $i; ?>][text]" rows="2" placeholder="Uw rit en afspraken worden vooraf vastgelegd."><?php echo esc_textarea($usp['text']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'faq') : ?>
                <?php
                $faq_home_edit_url = admin_url('admin.php?page=' . TaxiTheme_Setup::WIZARD_SLUG . '&tab=pages');
                // Probeer home page edit URL te vinden voor de link
                $faq_home_id = TaxiTheme_Installer::get_page_id('home');
                if ($faq_home_id) {
                    $faq_home_edit_url = self::edit_url($faq_home_id);
                }
                ?>
                <div class="tt-ed__group">
                    <div class="tt-ed__group-head">
                        <div class="tt-ed__group-head-text">
                            <h3><?php echo TaxiTheme_Icons::svg('info', 20); ?> FAQ vragen (20 slots)</h3>
                            <p>De eerste 5 komen van de <strong>homepage-FAQ</strong> (alleen-lezen op deze pagina). Vragen 6-20 verschijnen alleen op de FAQ-pagina.</p>
                        </div>
                    </div>

                    <div class="tt-ed__faq-grid">
                        <?php
                        // Slots 1-5: readonly preview van home FAQ items
                        for ($i = 0; $i < 5; $i++) :
                            $faq = $home_data['faq_items'][$i] ?? ['question' => '', 'answer' => ''];
                        ?>
                            <div class="tt-ed__faq-row tt-ed__faq-row--readonly">
                                <div class="tt-ed__faq-num tt-ed__faq-num--home">Home <?php echo $i + 1; ?></div>
                                <div class="tt-ed__faq-fields">
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Vraag <span class="tt-ed__hint">(homepage)</span></label>
                                        <input type="text" value="<?php echo esc_attr($faq['question']); ?>" disabled>
                                    </div>
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Antwoord</label>
                                        <textarea rows="2" disabled><?php echo esc_textarea($faq['answer']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>

                        <?php
                        // Slots 6-20: editable extras (faq_page_items[0..14])
                        for ($i = 0; $i < 15; $i++) :
                            $faq = $home_data['faq_page_items'][$i] ?? ['question' => '', 'answer' => ''];
                        ?>
                            <div class="tt-ed__faq-row">
                                <div class="tt-ed__faq-num">Extra <?php echo $i + 6; ?></div>
                                <div class="tt-ed__faq-fields">
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Vraag</label>
                                        <input type="text" name="page_extra[faq_page_items][<?php echo $i; ?>][question]" value="<?php echo esc_attr($faq['question']); ?>" placeholder="Bijv. Kan ik voor meerdere personen boeken?">
                                    </div>
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Antwoord</label>
                                        <textarea name="page_extra[faq_page_items][<?php echo $i; ?>][answer]" rows="2"><?php echo esc_textarea($faq['answer']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="tt-ed__faq-more">
                        <a href="<?php echo esc_url($faq_home_edit_url); ?>" class="tt-ed__faq-more-link">
                            Homepage-vragen bewerken
                            <?php echo TaxiTheme_Icons::svg('arrow-right', 14); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'tarieven') : ?>
                <?php
                $tv_vehicles     = TaxiTheme_Page_Meta::get_tarieven_vehicles($post_id);
                $tv_destinations = TaxiTheme_Page_Meta::get_tarieven_destinations($post_id);
                $tv_zones        = TaxiTheme_Page_Meta::get_tarieven_zones($post_id);
                ?>

                <!-- Vervoerstypes -->
                <div class="tt-ed__group">
                    <div class="tt-ed__group-head">
                        <div class="tt-ed__group-head-text">
                            <h3><?php echo TaxiTheme_Icons::svg('car', 20); ?> Vervoerstypes <span class="tt-ed__hint" style="font-weight:400;font-size:0.85rem;color:#6b7280;">(max 4 — leeg = niet getoond)</span></h3>
                            <p>Bijvoorbeeld <em>Personenauto</em>, <em>Busje</em> of <em>Rolstoelbus</em>. Elk type toont een foto + omschrijving + de 3 basistarieven.</p>
                        </div>
                    </div>
                    <div class="tt-ed__usps">
                        <?php foreach ($tv_vehicles as $i => $veh) : ?>
                            <div class="tt-ed__usp-row">
                                <div class="tt-ed__usp-num">Type <?php echo $i + 1; ?></div>
                                <div class="tt-ed__usp-grid">
                                    <div class="tt-ed__field">
                                        <label>Titel <span class="tt-ed__hint">(leeg = kaart weg)</span></label>
                                        <input type="text" name="page[tarieven_vehicles][<?php echo $i; ?>][title]" value="<?php echo esc_attr($veh['title']); ?>" placeholder="Bijv. Personenauto (max 4 personen)">
                                    </div>
                                    <div class="tt-ed__field">
                                        <label>Foto</label>
                                        <?php self::render_image_field('page[tarieven_vehicles][' . $i . '][image_id]', $veh['image_id']); ?>
                                    </div>
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Omschrijving</label>
                                        <textarea name="page[tarieven_vehicles][<?php echo $i; ?>][description]" rows="2" placeholder="Onze prijzen zijn transparant en zonder verrassingen."><?php echo esc_textarea($veh['description']); ?></textarea>
                                    </div>
                                    <div class="tt-ed__field">
                                        <label>Starttarief</label>
                                        <input type="text" name="page[tarieven_vehicles][<?php echo $i; ?>][starttarief]" value="<?php echo esc_attr($veh['starttarief']); ?>" placeholder="€ 4,15">
                                    </div>
                                    <div class="tt-ed__field">
                                        <label>Kilometertarief</label>
                                        <input type="text" name="page[tarieven_vehicles][<?php echo $i; ?>][kilometertarief]" value="<?php echo esc_attr($veh['kilometertarief']); ?>" placeholder="€ 3,05">
                                    </div>
                                    <div class="tt-ed__field">
                                        <label>Tijdstarief</label>
                                        <input type="text" name="page[tarieven_vehicles][<?php echo $i; ?>][tijdstarief]" value="<?php echo esc_attr($veh['tijdstarief']); ?>" placeholder="€ 0,50 / min">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Bestemmingen -->
                <div class="tt-ed__group">
                    <div class="tt-ed__group-head">
                        <div class="tt-ed__group-head-text">
                            <h3><?php echo TaxiTheme_Icons::svg('map-pin', 20); ?> Bestemmingen <span class="tt-ed__hint" style="font-weight:400;font-size:0.85rem;color:#6b7280;">(bijv. luchthavens)</span></h3>
                            <p>Één blok met een lijst bestemmingen en hun tarief. Bijvoorbeeld voor luchthavenvervoer.</p>
                        </div>
                    </div>
                    <div class="tt-ed__tariff-intro">
                        <div class="tt-ed__field-stack">
                            <div class="tt-ed__field">
                                <label>Sectie-titel <span class="tt-ed__hint">(leeg = hele blok weg)</span></label>
                                <input type="text" name="page[tarieven_destinations][title]" value="<?php echo esc_attr($tv_destinations['title']); ?>" placeholder="Bijv. Luchthaven vervoer">
                            </div>
                            <div class="tt-ed__field">
                                <label>Omschrijving <span class="tt-ed__hint">(optioneel)</span></label>
                                <textarea name="page[tarieven_destinations][description]" rows="4" placeholder="Tarieven vanaf Middelburg naar de belangrijkste luchthavens."><?php echo esc_textarea($tv_destinations['description']); ?></textarea>
                            </div>
                        </div>
                        <div class="tt-ed__field">
                            <label>Foto</label>
                            <?php self::render_image_field('page[tarieven_destinations][image_id]', $tv_destinations['image_id']); ?>
                        </div>
                    </div>
                    <div class="tt-ed__tariff-destinations">
                        <?php foreach ($tv_destinations['items'] as $i => $it) : ?>
                            <div class="tt-ed__tariff-destination">
                                <label>#<?php echo $i + 1; ?></label>
                                <input type="text" name="page[tarieven_destinations][items][<?php echo $i; ?>][label]" value="<?php echo esc_attr($it['label']); ?>" placeholder="Bestemming">
                                <input type="text" name="page[tarieven_destinations][items][<?php echo $i; ?>][price]" value="<?php echo esc_attr($it['price']); ?>" placeholder="Prijs (bv. €250)">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Regionale zones -->
                <div class="tt-ed__group">
                    <div class="tt-ed__group-head">
                        <div class="tt-ed__group-head-text">
                            <h3><?php echo TaxiTheme_Icons::svg('map-pin', 20); ?> Regionale zones <span class="tt-ed__hint" style="font-weight:400;font-size:0.85rem;color:#6b7280;">(gegroepeerde bestemmingen)</span></h3>
                            <p>Voor lokale ritten met vaste richtprijzen — groepeer bestemmingen per categorie (bijv. <em>korte ritten</em>, <em>badplaatsen</em>, <em>andere Zeeuwse steden</em>). Max 4 groepen, elk max 8 bestemmingen.</p>
                        </div>
                    </div>
                    <div class="tt-ed__grid">
                        <div class="tt-ed__field">
                            <label>Sectie-titel <span class="tt-ed__hint">(leeg = hele blok weg)</span></label>
                            <input type="text" name="page[tarieven_zones][title]" value="<?php echo esc_attr($tv_zones['title']); ?>" placeholder="Bijv. Lokale ritten en richtprijzen">
                        </div>
                        <div class="tt-ed__field">
                            <label>Ondertitel <span class="tt-ed__hint">(optioneel)</span></label>
                            <input type="text" name="page[tarieven_zones][subtitle]" value="<?php echo esc_attr($tv_zones['subtitle']); ?>" placeholder="Plan uw rit eenvoudig met onze richtprijzen">
                        </div>
                    </div>
                    <div class="tt-ed__tariff-zones">
                        <?php foreach ($tv_zones['groups'] as $gi => $g) : ?>
                            <div class="tt-ed__tariff-zone">
                                <div class="tt-ed__tariff-zone-label">
                                    <span>GROEP <?php echo $gi + 1; ?></span>
                                </div>
                                <div class="tt-ed__tariff-zone-head">
                                    <div class="tt-ed__field">
                                        <label>Icoon</label>
                                        <select name="page[tarieven_zones][groups][<?php echo $gi; ?>][icon]">
                                            <option value="">— geen —</option>
                                            <?php foreach ($icon_options as $ico) : ?>
                                                <option value="<?php echo esc_attr($ico); ?>" <?php selected($g['icon'], $ico); ?>><?php echo esc_html($ico); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="tt-ed__field">
                                        <label>Groep-titel <span class="tt-ed__hint">(leeg = weg)</span></label>
                                        <input type="text" name="page[tarieven_zones][groups][<?php echo $gi; ?>][title]" value="<?php echo esc_attr($g['title']); ?>" placeholder="Bijv. Korte ritten">
                                    </div>
                                </div>
                                <div>
                                    <label class="tt-ed__tariff-rows-label">Bestemmingen in deze groep</label>
                                    <div class="tt-ed__tariff-rows">
                                        <?php foreach ($g['rows'] as $ri => $r) : ?>
                                            <div class="tt-ed__tariff-row">
                                                <input type="text" name="page[tarieven_zones][groups][<?php echo $gi; ?>][rows][<?php echo $ri; ?>][label]" value="<?php echo esc_attr($r['label']); ?>" placeholder="Bestemming">
                                                <input type="text" name="page[tarieven_zones][groups][<?php echo $gi; ?>][rows][<?php echo $ri; ?>][price]" value="<?php echo esc_attr($r['price']); ?>" placeholder="€30">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'diensten') : ?>
                <div class="tt-ed__group">
                    <div class="tt-ed__group-head">
                        <div class="tt-ed__group-head-text">
                            <h3><?php echo TaxiTheme_Icons::svg('briefcase', 20); ?> Dienstdetails</h3>
                            <p>Vertel hier uitgebreid over iedere dienst. Gebruik lege regels om de tekst in duidelijke alinea's te verdelen. De titel, korte tekst en het icoon beheer je in de homepage-editor.</p>
                        </div>
                    </div>
                    <div class="tt-ed__usps">
                        <?php
                        // Altijd 6 slots — pad met lege entries
                        $diensten_items = array_pad($home_data['services_items'], 6, ['icon' => 'car', 'title' => '', 'text' => '', 'link_url' => '', 'link_label' => '', 'home_image_id' => 0, 'image_id' => 0, 'long_text' => '', 'features' => '', 'price' => '']);
                        foreach ($diensten_items as $i => $svc) :
                            $svc_label = !empty($svc['title']) ? $svc['title'] : 'Dienst ' . ($i + 1);
                        ?>
                            <div class="tt-ed__usp-row">
                                <div class="tt-ed__usp-num"><?php echo esc_html($svc_label); ?></div>
                                <div class="tt-ed__usp-grid">
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Afbeelding <span class="tt-ed__hint">(optioneel — verschijnt naast de tekst)</span></label>
                                        <?php self::render_image_field('page_extra[services_items][' . $i . '][image_id]', $svc['image_id'] ?? 0); ?>
                                    </div>
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Uitgebreide beschrijving <span class="tt-ed__hint">(meerdere alinea's mogelijk)</span></label>
                                        <textarea name="page_extra[services_items][<?php echo $i; ?>][long_text]" rows="9" placeholder="Beschrijf de dienst uitgebreid.&#10;&#10;Begin een nieuwe alinea met een lege regel."><?php echo esc_textarea($svc['long_text'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Kenmerken <span class="tt-ed__hint">(één per regel — worden checkbullets)</span></label>
                                        <textarea name="page_extra[services_items][<?php echo $i; ?>][features]" rows="4" placeholder="Vluchttracking&#10;Meet & Greet met naambord&#10;Gratis bagage-hulp"><?php echo esc_textarea($svc['features'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Prijs-label <span class="tt-ed__hint">(bijv. "Vanaf € 45")</span></label>
                                        <input type="text" name="page_extra[services_items][<?php echo $i; ?>][price]" value="<?php echo esc_attr($svc['price'] ?? ''); ?>" placeholder="Vanaf € 45">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // Per-page CTA banner override — leeg laten = home defaults gebruiken
            $page_cta = TaxiTheme_Page_Meta::get_cta_override($post_id);
            $home_content = TaxiTheme_Home_Content::all();
            ?>
            <div class="tt-ed__group">
                <div class="tt-ed__group-head">
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('phone', 20); ?> CTA-banner (onderaan de pagina)</h3>
                            <p>Geef deze pagina eventueel een eigen titel en knoppen. Laat velden leeg om de standaard uit de homepage-editor te gebruiken.</p>
                    </div>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel <span class="tt-ed__hint">(homepage: "<?php echo esc_html($home_content['contact_title']); ?>")</span></label>
                        <input type="text" name="page[cta_title]" value="<?php echo esc_attr($page_cta['title']); ?>" placeholder="Laat leeg voor standaard">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel <span class="tt-ed__hint">(homepage: "<?php echo esc_html($home_content['contact_subtitle']); ?>")</span></label>
                        <input type="text" name="page[cta_subtitle]" value="<?php echo esc_attr($page_cta['subtitle']); ?>" placeholder="Laat leeg voor standaard">
                    </div>
                </div>

                <div class="tt-ed__preset-note" style="background:#fef3c7;border-color:#fcd34d;color:#78350f;margin-top:14px;">
                    <?php echo TaxiTheme_Icons::svg('info', 16); ?>
                    Standaard-buttons: <strong>Bel ons</strong> (telefoonnummer uit Bedrijfsgegevens) + <strong>Mail ons</strong> (e-mail). Vul hieronder eigen buttons in om die te overschrijven.
                </div>

                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Button 1 – label <span class="tt-ed__hint">(leeg = Bel ons)</span></label>
                        <input type="text" name="page[cta1_label]" value="<?php echo esc_attr($page_cta['cta1_label']); ?>" placeholder="Bijv. Boek een taxi">
                    </div>
                    <div class="tt-ed__field">
                        <label>Button 1 – link</label>
                        <input type="text" name="page[cta1_url]" value="<?php echo esc_attr($page_cta['cta1_url']); ?>" placeholder="/boeken of tel:0612345678">
                    </div>
                    <div class="tt-ed__field">
                        <label>Button 2 – label <span class="tt-ed__hint">(leeg = Mail ons)</span></label>
                        <input type="text" name="page[cta2_label]" value="<?php echo esc_attr($page_cta['cta2_label']); ?>" placeholder="Bijv. WhatsApp">
                    </div>
                    <div class="tt-ed__field">
                        <label>Button 2 – link</label>
                        <input type="text" name="page[cta2_url]" value="<?php echo esc_attr($page_cta['cta2_url']); ?>" placeholder="https://wa.me/... of leeg">
                    </div>
                </div>
            </div>

            <?php
            // Hidden inputs voor sections zodat bestaande data behouden blijft.
            // Editor UI is verwijderd maar de post_meta kan nog gevuld zijn.
            foreach ($sections as $i => $sec) {
                echo '<input type="hidden" name="page[sections][' . $i . '][title]" value="' . esc_attr($sec['title']) . '">';
                echo '<input type="hidden" name="page[sections][' . $i . '][text]" value="' . esc_attr($sec['text']) . '">';
                echo '<input type="hidden" name="page[sections][' . $i . '][image_id]" value="' . (int) ($sec['image_id'] ?? 0) . '">';
            }
            ?>

            <div class="tt-ed__actions">
                <button type="submit" class="tt-ed__btn tt-ed__btn--primary">Wijzigingen opslaan</button>
                <?php if (self::$saved) : ?>
                    <span class="tt-ed__saved-indicator" data-auto-hide="1">✓ Wijzigingen opgeslagen</span>
                <?php endif; ?>
            </div>
        </form>
        <?php
        self::render_image_picker_js();
    }

    private static function render_home_editor($post_id) {
        $data = TaxiTheme_Home_Content::all();
        ?>
        <form method="post">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
            <input type="hidden" name="post" value="<?php echo (int) $post_id; ?>">

            <?php
            $preset       = TaxiTheme_Preset::current();
            $preset_label = TaxiTheme_Preset::label($preset);

            // Sectie-volgorde: default is de defaults array; missing keys worden
            // achteraan geplakt zodat nieuwe secties na een theme update meekomen.
            $current_order = $data['section_order'] ?? array_keys(TaxiTheme_Home_Content::REORDERABLE_SECTIONS);
            $current_order = array_values(array_unique(array_intersect($current_order, array_keys(TaxiTheme_Home_Content::REORDERABLE_SECTIONS))));
            foreach (array_keys(TaxiTheme_Home_Content::REORDERABLE_SECTIONS) as $k) {
                if (!in_array($k, $current_order, true)) $current_order[] = $k;
            }
            ?>

            <?php
            // post-content en andere keys zonder editor-panel via hidden input
            // (staat niet in sidebar). Sanitize plakt anders sowieso achteraan.
            $preserved_keys = [];
            foreach ($current_order as $key) {
                if (!isset(TaxiTheme_Home_Content::REORDERABLE_SECTIONS[$key])) continue;
                $preserved_keys[$key] = true;
            }
            $keys_without_panel = ['post-content'];
            foreach ($keys_without_panel as $key) {
                if (!isset($preserved_keys[$key])) continue;
                echo '<input type="hidden" name="home[section_order][]" value="' . esc_attr($key) . '" data-order-fallback="1">';
            }

            // Section config: bevat toggle-veld naam per section-key.
            $section_toggle_map = [
                'trust'        => 'trust_enabled',
                'usps'         => 'usps_enabled',
                'about'        => 'about_enabled',
                'steps'        => 'steps_enabled',
                'spotlight'    => 'spotlight_enabled',
                'features'     => 'features_enabled',
                'services'     => 'services_enabled',
                'waarom'       => 'waarom_enabled',
                'service-area' => 'service_area_enabled',
                'routes'       => 'routes_enabled',
                'faq'          => 'faq_enabled',
                'fleet'        => 'fleet_enabled',
                'reviews'      => 'reviews_enabled',
            ];

            // Gecontroleerde achtergrondkeuzes per preset. De previews komen
            // zoveel mogelijk uit het actieve kleurenpalet.
            $palette_slug     = '';
            $palette_swatches = [];
            if (class_exists('TaxiTheme_Theme_Variant')) {
                $palette_slug = TaxiTheme_Theme_Variant::current();
                $palettes     = TaxiTheme_Theme_Variant::all();
                $palette_swatches = $palettes[$palette_slug]['swatch'] ?? [];
            }

            $background_options = [
                'klassiek' => [
                    ['value' => 'auto',    'label' => 'Automatisch', 'preview' => 'linear-gradient(135deg, #fdfcf7 0 50%, #ffffff 50% 100%)'],
                    ['value' => 'base',    'label' => $palette_slug === 'klassiek-licht' ? 'Licht' : 'Crème', 'preview' => $palette_swatches[2] ?? '#fdfcf7'],
                    ['value' => 'surface', 'label' => 'Wit',         'preview' => '#ffffff'],
                    ['value' => 'dark',    'label' => 'Donker',      'preview' => $palette_slug === 'klassiek-licht' ? '#0f172a' : '#14161f'],
                    ['value' => 'accent',  'label' => 'Accent',      'preview' => $palette_swatches[1] ?? '#f5b800'],
                ],
                'bold' => [
                    ['value' => 'auto',      'label' => 'Automatisch', 'preview' => 'linear-gradient(135deg, #0b0d14 0 50%, #14161f 50% 100%)'],
                    ['value' => 'base',      'label' => 'Zwart',       'preview' => '#0b0d14'],
                    ['value' => 'alternate', 'label' => 'Donkergrijs', 'preview' => '#14161f'],
                    ['value' => 'light',     'label' => 'Crème',       'preview' => '#fdfcf7'],
                    ['value' => 'white',     'label' => 'Wit',         'preview' => '#ffffff'],
                    ['value' => 'accent',    'label' => 'Accent',      'preview' => $palette_swatches[1] ?? '#f5b800'],
                ],
                'onepage' => [
                    ['value' => 'auto',    'label' => 'Automatisch', 'preview' => 'linear-gradient(135deg, #0a0b10 0 50%, #1a1a24 50% 100%)'],
                    ['value' => 'base',    'label' => 'Zwart',       'preview' => $palette_swatches[0] ?? '#0a0b10'],
                    ['value' => 'surface', 'label' => 'Donker vlak', 'preview' => $palette_swatches[2] ?? '#1a1a24'],
                    ['value' => 'accent',  'label' => 'Accent',      'preview' => $palette_swatches[1] ?? '#f5b800'],
                ],
                'premium' => [
                    ['value' => 'auto',    'label' => 'Automatisch', 'preview' => 'linear-gradient(135deg, #ffffff 0 50%, #f8fafc 50% 100%)'],
                    ['value' => 'base',    'label' => 'Wit',         'preview' => '#ffffff'],
                    ['value' => 'surface', 'label' => 'Lichtgrijs',  'preview' => '#f8fafc'],
                    ['value' => 'dark',    'label' => 'Navy',        'preview' => '#0f172a'],
                    ['value' => 'accent',  'label' => 'Accent',      'preview' => $palette_swatches[1] ?? '#f59e0b'],
                ],
                'simpel' => [
                    ['value' => 'auto',     'label' => 'Automatisch', 'preview' => 'linear-gradient(135deg, ' . ($palette_swatches[0] ?? '#0a0a0a') . ' 0 50%, ' . ($palette_swatches[2] ?? '#ffffff') . ' 50% 100%)'],
                    ['value' => 'base',     'label' => $palette_slug === 'simpel-licht' ? 'Wit' : 'Zwart', 'preview' => $palette_swatches[0] ?? '#0a0a0a'],
                    ['value' => 'surface',  'label' => $palette_slug === 'simpel-licht' ? 'Lichtgrijs' : 'Donkergrijs', 'preview' => $palette_slug === 'simpel-licht' ? '#f5f5f5' : '#1a1a1a'],
                    ['value' => 'contrast', 'label' => 'Contrast',    'preview' => $palette_swatches[2] ?? '#ffffff'],
                    ['value' => 'accent',   'label' => 'Accent',      'preview' => $palette_swatches[1] ?? '#00bcd4'],
                ],
            ];
            $current_backgrounds = $data['section_backgrounds'][$preset] ?? [];
            $background_config = [
                'enabled' => isset($background_options[$preset]),
                'preset'  => $preset,
                'values'  => $current_backgrounds,
                'options' => $background_options[$preset] ?? [],
            ];
            ?>

            <div id="tt-ed-background-config" data-config="<?php echo esc_attr(wp_json_encode($background_config)); ?>" hidden>
                <?php foreach (array_keys($background_options) as $background_preset) : ?>
                    <?php foreach (array_keys(TaxiTheme_Home_Content::REORDERABLE_SECTIONS) as $background_key) : ?>
                        <input
                            type="hidden"
                            class="tt-ed__background-fallback"
                            data-preset="<?php echo esc_attr($background_preset); ?>"
                            data-section-key="<?php echo esc_attr($background_key); ?>"
                            name="home[section_backgrounds][<?php echo esc_attr($background_preset); ?>][<?php echo esc_attr($background_key); ?>]"
                            value="<?php echo esc_attr($data['section_backgrounds'][$background_preset][$background_key] ?? 'auto'); ?>"
                        >
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <div class="tt-ed__layout">
                <!-- Left sidebar: sectie-lijst met toggle proxy + up/down + jump link -->
                <aside class="tt-ed__sidebar" id="tt-ed-sidebar">
                    <div class="tt-ed__sidebar-head">
                        <strong>Homepage-indeling</strong>
                        <button type="button" class="tt-ed__sidebar-toggle" title="Navigatie inklappen" aria-label="Navigatie inklappen" aria-expanded="true">−</button>
                    </div>
                    <div class="tt-ed__sidebar-section-label">Bovenkant</div>
                    <a href="#tt-ed-section-hero" class="tt-ed__sidebar-static tt-ed__sidebar-jump">Hero</a>
                    <div class="tt-ed__sidebar-section-label">Secties</div>
                    <ol class="tt-ed__sidebar-list">
                        <?php foreach ($current_order as $key) :
                            // Skip keys zonder editor-panel (bv. post-content is WP's default page editor)
                            if (in_array($key, ['post-content'], true)) continue;
                            $label      = TaxiTheme_Home_Content::REORDERABLE_SECTIONS[$key] ?? $key;
                            $toggle_key = $section_toggle_map[$key] ?? null;
                            $is_enabled = $toggle_key ? !empty($data[$toggle_key]) : true;
                        ?>
                            <li class="tt-ed__sidebar-row <?php echo $is_enabled ? 'is-enabled' : ''; ?>" data-section-key="<?php echo esc_attr($key); ?>" <?php if ($toggle_key) echo 'data-toggle-name="home[' . esc_attr($toggle_key) . ']"'; ?>>
                                <a href="#tt-ed-section-<?php echo esc_attr($key); ?>" class="tt-ed__sidebar-jump">
                                    <?php echo esc_html($label); ?>
                                </a>
                                <button type="button" class="tt-ed__sidebar-btn tt-ed__sidebar-btn--up" data-dir="up" title="Omhoog" aria-label="<?php echo esc_attr($label); ?> omhoog verplaatsen">↑</button>
                                <button type="button" class="tt-ed__sidebar-btn tt-ed__sidebar-btn--down" data-dir="down" title="Omlaag" aria-label="<?php echo esc_attr($label); ?> omlaag verplaatsen">↓</button>
                                <?php if ($toggle_key) : ?>
                                    <button type="button" class="tt-ed__sidebar-switch <?php echo $is_enabled ? 'is-on' : ''; ?>" title="Tonen of verbergen" aria-label="<?php echo esc_attr($label); ?> tonen of verbergen" aria-pressed="<?php echo $is_enabled ? 'true' : 'false'; ?>">
                                        <span class="tt-ed__sidebar-switch-track"><span class="tt-ed__sidebar-switch-thumb"></span></span>
                                    </button>
                                <?php else : ?>
                                    <span class="tt-ed__sidebar-switch-placeholder" aria-hidden="true"></span>
                                <?php endif; ?>
                                <input type="hidden" name="home[section_order][]" value="<?php echo esc_attr($key); ?>">
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <div class="tt-ed__sidebar-section-label">Onderkant</div>
                    <a href="#tt-ed-section-contact-cta" class="tt-ed__sidebar-static tt-ed__sidebar-jump">Contact CTA</a>
                    <p class="tt-ed__sidebar-help">Klik op een naam om dat onderdeel te bewerken. Gebruik de pijlen voor de volgorde en de schakelaar voor tonen of verbergen.</p>
                </aside>

                <div class="tt-ed__main">
            <!-- Hero -->
            <div class="tt-ed__group" id="tt-ed-section-hero">
                <div class="tt-ed__group-head">
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('target', 20); ?> Hero</h3>
                        <p>Bovenste onderdeel met bedrijfsnaam en boekingsformulier.</p>
                    </div>
                </div>

                <?php if ($preset === 'klassiek') : ?>
                    <div class="tt-ed__field tt-ed__field--full" style="margin-bottom:24px;">
                        <label>Stijl</label>
                        <div class="tt-ed__hero-picker">
                            <?php
                            $hero_styles = [
                                'split'    => ['label' => 'Split', 'desc' => 'Tekst + booking form'],
                                'stacked'  => ['label' => 'Stacked', 'desc' => 'Tekst boven, form onder'],
                                'centered' => ['label' => 'Centered', 'desc' => 'Alleen tekst + knoppen'],
                            ];
                            foreach ($hero_styles as $key => $meta) :
                                $selected = ($data['hero_style'] ?? 'split') === $key;
                            ?>
                                <label class="tt-ed__hero-opt <?php echo $selected ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="home[hero_style]" value="<?php echo esc_attr($key); ?>" <?php checked($selected); ?>>
                                    <div class="tt-ed__hero-mock tt-ed__hero-mock--<?php echo esc_attr($key); ?>">
                                        <?php self::render_hero_mock($key); ?>
                                    </div>
                                    <div class="tt-ed__hero-opt-label"><?php echo esc_html($meta['label']); ?></div>
                                    <div class="tt-ed__hero-opt-desc"><?php echo esc_html($meta['desc']); ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="tt-ed__toggle-row tt-ed__toggle-row--single" data-only-style="split">
                        <label class="tt-ed__toggle-inline">
                            <input type="checkbox" name="home[hero_accent_enabled]" value="1" <?php checked($data['hero_accent_enabled'], 1); ?>>
                            <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                            <span class="tt-ed__toggle-label">Gele accents achter form</span>
                        </label>
                        <span class="tt-ed__hint">(alleen bij Split stijl)</span>
                    </div>
                <?php else : ?>
                    <?php
                    // Preserve bestaande waarden door hidden inputs — anders reset sanitize() ze naar default bij save
                    ?>
                    <input type="hidden" name="home[hero_style]" value="<?php echo esc_attr($data['hero_style']); ?>">
                    <input type="hidden" name="home[hero_accent_enabled]" value="<?php echo (int) $data['hero_accent_enabled']; ?>">
                <?php endif; ?>

                <div class="tt-ed__toggle-row">
                    <label class="tt-ed__toggle-inline">
                        <input type="checkbox" name="home[hero_title_enabled]" value="1" <?php checked($data['hero_title_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                        <span class="tt-ed__toggle-label">Titel</span>
                    </label>
                    <input type="text" name="home[hero_title]" value="<?php echo esc_attr($data['hero_title']); ?>" placeholder="Bedrijfsnaam (uit Bedrijfsgegevens)">
                </div>

                <div class="tt-ed__toggle-row">
                    <label class="tt-ed__toggle-inline">
                        <input type="checkbox" name="home[hero_subtitle_enabled]" value="1" <?php checked($data['hero_subtitle_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                        <span class="tt-ed__toggle-label">Subtitel</span>
                    </label>
                    <input type="text" name="home[hero_subtitle]" value="<?php echo esc_attr($data['hero_subtitle']); ?>" placeholder="Tagline (uit Bedrijfsgegevens)">
                </div>

                <div class="tt-ed__toggle-row">
                    <label class="tt-ed__toggle-inline">
                        <input type="checkbox" name="home[hero_eyebrow_enabled]" value="1" <?php checked($data['hero_eyebrow_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                        <span class="tt-ed__toggle-label">Eyebrow</span>
                    </label>
                    <input type="text" name="home[hero_eyebrow]" value="<?php echo esc_attr($data['hero_eyebrow']); ?>" placeholder="bv. Taxi in Amsterdam (leeg = auto)">
                </div>

                <div class="tt-ed__toggle-row tt-ed__toggle-row--single">
                    <label class="tt-ed__toggle-inline">
                        <input type="checkbox" name="home[hero_phone_enabled]" value="1" <?php checked($data['hero_phone_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                        <span class="tt-ed__toggle-label">Bel-knop tonen</span>
                    </label>
                    <span class="tt-ed__hint">(gebruikt telefoonnummer uit Bedrijfsgegevens)</span>
                </div>

                <div class="tt-ed__toggle-row">
                    <label class="tt-ed__toggle-inline">
                        <input type="checkbox" name="home[hero_whatsapp_enabled]" value="1" <?php checked($data['hero_whatsapp_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                        <span class="tt-ed__toggle-label">WhatsApp-knop</span>
                    </label>
                    <input type="text" name="home[hero_whatsapp_number]" value="<?php echo esc_attr($data['hero_whatsapp_number']); ?>" placeholder="Nummer (leeg = telefoon uit Bedrijfsgegevens)">
                </div>

                <?php if ($preset === 'klassiek') : ?>
                    <div class="tt-ed__toggle-row tt-ed__toggle-row--stacked">
                        <label class="tt-ed__toggle-inline">
                            <input type="checkbox" name="home[hero_bullets_enabled]" value="1" <?php checked($data['hero_bullets_enabled'], 1); ?>>
                            <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                            <span class="tt-ed__toggle-label">Bulletpoints</span>
                        </label>
                        <div class="tt-ed__bullets">
                            <?php for ($i = 0; $i < 5; $i++) :
                                $val = $data['hero_bullets'][$i] ?? '';
                            ?>
                                <div class="tt-ed__bullet-row">
                                    <span class="tt-ed__bullet-num"><?php echo $i + 1; ?></span>
                                    <input type="text" name="home[hero_bullets][<?php echo $i; ?>]" value="<?php echo esc_attr($val); ?>" placeholder="<?php echo $i < 3 ? 'Bulletpoint' : 'Optioneel'; ?>">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php else :
                    // Preserve stored waarden zodat sanitize() ze niet reset bij save
                    echo '<input type="hidden" name="home[hero_bullets_enabled]" value="' . (int) $data['hero_bullets_enabled'] . '">';
                    foreach ($data['hero_bullets'] as $i => $val) {
                        echo '<input type="hidden" name="home[hero_bullets][' . $i . ']" value="' . esc_attr($val) . '">';
                    }
                endif; ?>

                <?php if (in_array($preset, ['klassiek', 'onepage'], true)) : ?>
                    <div class="tt-ed__toggle-row tt-ed__toggle-row--stacked">
                        <label class="tt-ed__toggle-inline">
                            <input type="checkbox" name="home[hero_tags_enabled]" value="1" <?php checked($data['hero_tags_enabled'], 1); ?>>
                            <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                            <span class="tt-ed__toggle-label">Tags</span>
                        </label>
                        <div class="tt-ed__bullets">
                            <?php for ($i = 0; $i < 5; $i++) :
                                $val = $data['hero_tags'][$i] ?? '';
                            ?>
                                <div class="tt-ed__bullet-row">
                                    <span class="tt-ed__bullet-num tt-ed__bullet-num--tag">T<?php echo $i + 1; ?></span>
                                    <input type="text" name="home[hero_tags][<?php echo $i; ?>]" value="<?php echo esc_attr($val); ?>" placeholder="<?php echo $i < 3 ? 'bv. ★ 4.9 · 1.200+ ritten' : 'Optioneel'; ?>">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php else :
                    echo '<input type="hidden" name="home[hero_tags_enabled]" value="' . (int) $data['hero_tags_enabled'] . '">';
                    foreach ($data['hero_tags'] as $i => $val) {
                        echo '<input type="hidden" name="home[hero_tags][' . $i . ']" value="' . esc_attr($val) . '">';
                    }
                endif; ?>

                <?php if ($preset === 'premium') : ?>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>Hero-stijl</label>
                        <div class="tt-ed__hero-picker">
                            <?php
                            $premium_styles = [
                                'image' => ['label' => 'Cinematic',    'desc' => 'Centered titel op grote foto (of gradient) — Apple-esthetiek'],
                                'form'  => ['label' => 'Met formulier', 'desc' => 'Tekst links, boekingsformulier rechts in white card'],
                            ];
                            foreach ($premium_styles as $key => $meta) :
                                $selected = ($data['hero_premium_style'] ?? 'image') === $key;
                                $mock = $key === 'image' ? 'centered' : 'split';
                            ?>
                                <label class="tt-ed__hero-opt <?php echo $selected ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="home[hero_premium_style]" value="<?php echo esc_attr($key); ?>" <?php checked($selected); ?>>
                                    <div class="tt-ed__hero-mock tt-ed__hero-mock--<?php echo esc_attr($mock); ?>">
                                        <?php self::render_hero_mock($mock); ?>
                                    </div>
                                    <div class="tt-ed__hero-opt-label"><?php echo esc_html($meta['label']); ?></div>
                                    <div class="tt-ed__hero-opt-desc"><?php echo esc_html($meta['desc']); ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="tt-ed__toggle-row tt-ed__toggle-row--stacked">
                        <div class="tt-ed__field-head">
                            <strong>Hero-afbeelding</strong>
                            <span class="tt-ed__hint">(leeg = gradient fallback — gebruikt bij beide stijlen als bg)</span>
                        </div>
                        <?php self::render_image_field('home[hero_image_id]', $data['hero_image_id']); ?>
                    </div>
                <?php else :
                    echo '<input type="hidden" name="home[hero_image_id]" value="' . (int) $data['hero_image_id'] . '">';
                    echo '<input type="hidden" name="home[hero_premium_style]" value="' . esc_attr($data['hero_premium_style']) . '">';
                endif; ?>

                <?php if ($preset === 'bold') : ?>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>Hero-stijl</label>
                        <div class="tt-ed__hero-picker">
                            <?php
                            $bold_styles = [
                                'fullscreen' => ['label' => 'Fullscreen',    'desc' => 'Grote display-titel + prominente CTAs (Boek / Bel / WhatsApp)'],
                                'form'       => ['label' => 'Met formulier', 'desc' => 'Tekst links, boekingsformulier direct rechts op dark bg'],
                            ];
                            foreach ($bold_styles as $key => $meta) :
                                $selected = ($data['hero_bold_style'] ?? 'fullscreen') === $key;
                                $mock = $key === 'fullscreen' ? 'centered' : 'split';
                            ?>
                                <label class="tt-ed__hero-opt <?php echo $selected ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="home[hero_bold_style]" value="<?php echo esc_attr($key); ?>" <?php checked($selected); ?>>
                                    <div class="tt-ed__hero-mock tt-ed__hero-mock--<?php echo esc_attr($mock); ?>">
                                        <?php self::render_hero_mock($mock); ?>
                                    </div>
                                    <div class="tt-ed__hero-opt-label"><?php echo esc_html($meta['label']); ?></div>
                                    <div class="tt-ed__hero-opt-desc"><?php echo esc_html($meta['desc']); ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else :
                    echo '<input type="hidden" name="home[hero_bold_style]" value="' . esc_attr($data['hero_bold_style']) . '">';
                endif; ?>

                <?php if ($preset === 'simpel') : ?>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>Hero-stijl</label>
                        <div class="tt-ed__hero-picker">
                            <?php
                            $simpel_styles = [
                                'image' => ['label' => 'Met afbeelding', 'desc' => 'Tekst + CTA-knoppen links, auto-foto rechts'],
                                'form'  => ['label' => 'Met formulier',  'desc' => 'Tekst links, boekingsformulier direct rechts'],
                            ];
                            foreach ($simpel_styles as $key => $meta) :
                                $selected = ($data['hero_simpel_style'] ?? 'image') === $key;
                            ?>
                                <label class="tt-ed__hero-opt <?php echo $selected ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="home[hero_simpel_style]" value="<?php echo esc_attr($key); ?>" <?php checked($selected); ?>>
                                    <div class="tt-ed__hero-mock tt-ed__hero-mock--<?php echo esc_attr($key === 'image' ? 'split' : 'split'); ?>">
                                        <?php self::render_hero_mock($key === 'image' ? 'split' : 'split'); ?>
                                    </div>
                                    <div class="tt-ed__hero-opt-label"><?php echo esc_html($meta['label']); ?></div>
                                    <div class="tt-ed__hero-opt-desc"><?php echo esc_html($meta['desc']); ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="tt-ed__toggle-row tt-ed__toggle-row--stacked">
                        <div class="tt-ed__field-head">
                            <strong>Auto-afbeelding</strong>
                            <span class="tt-ed__hint">(alleen zichtbaar bij hero-stijl "Met afbeelding")</span>
                        </div>
                        <?php self::render_image_field('home[hero_simpel_image_id]', $data['hero_simpel_image_id']); ?>
                    </div>
                <?php else :
                    echo '<input type="hidden" name="home[hero_simpel_style]" value="' . esc_attr($data['hero_simpel_style']) . '">';
                    echo '<input type="hidden" name="home[hero_simpel_image_id]" value="' . (int) $data['hero_simpel_image_id'] . '">';
                endif; ?>
            </div>

            <!-- Trust bar -->
            <div class="tt-ed__group" data-section-key="trust">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('trust'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('shield', 20); ?> Trust bar</h3>
                        <p>Smalle balk onder de hero met vertrouwens-items (rating, KvK, betalingen, etc.).</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[trust_enabled]" value="1" <?php checked($data['trust_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>

                <div class="tt-ed__trust">
                    <?php
                    $trust_icons = TaxiTheme_Icons::usp_choices();
                    for ($i = 0; $i < 4; $i++) :
                        $item = $data['trust_items'][$i] ?? ['icon' => 'star', 'text' => ''];
                    ?>
                        <div class="tt-ed__trust-row">
                            <div class="tt-ed__trust-num">Item <?php echo $i + 1; ?></div>
                            <div class="tt-ed__field">
                                <label>Icoon</label>
                                <select name="home[trust_items][<?php echo $i; ?>][icon]" class="tt-ed__select">
                                    <?php foreach ($trust_icons as $ic) : ?>
                                        <option value="<?php echo esc_attr($ic); ?>" <?php selected(($item['icon'] ?? '') === $ic); ?>><?php echo esc_html($ic); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="tt-ed__field tt-ed__field--grow">
                                <label>Tekst</label>
                                <input type="text" name="home[trust_items][<?php echo $i; ?>][text]" value="<?php echo esc_attr($item['text'] ?? ''); ?>" placeholder="bv. 4.9 op Google">
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- USPs -->
            <div class="tt-ed__group" data-section-key="usps">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('usps'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('sparkles', 20); ?> USPs (3 blokken)</h3>
                        <p>Voordelen-blokken direct onder de hero.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[usps_enabled]" value="1" <?php checked($data['usps_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>

                <div class="tt-ed__usps">
                    <?php
                    $icon_choices = TaxiTheme_Icons::usp_choices();
                    foreach ($data['usps'] as $i => $usp) : ?>
                        <div class="tt-ed__usp-row">
                            <div class="tt-ed__usp-num">USP <?php echo $i + 1; ?></div>
                            <div class="tt-ed__usp-grid">
                                <div class="tt-ed__field tt-ed__field--icon-picker">
                                    <label>Icoon</label>
                                    <div class="tt-ed__icon-picker">
                                        <?php foreach ($icon_choices as $name) :
                                            $selected = $usp['icon'] === $name;
                                        ?>
                                            <label class="tt-ed__icon-opt <?php echo $selected ? 'is-selected' : ''; ?>" title="<?php echo esc_attr($name); ?>">
                                                <input type="radio" name="home[usps][<?php echo $i; ?>][icon]" value="<?php echo esc_attr($name); ?>" <?php checked($selected); ?>>
                                                <?php echo TaxiTheme_Icons::svg($name, 20); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Titel</label>
                                    <input type="text" name="home[usps][<?php echo $i; ?>][title]" value="<?php echo esc_attr($usp['title']); ?>">
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Beschrijving</label>
                                    <textarea name="home[usps][<?php echo $i; ?>][text]" rows="3"><?php echo esc_textarea($usp['text']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Over ons / About -->
            <div class="tt-ed__group" data-section-key="about">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('about'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('info', 20); ?> Over ons</h3>
                        <p>Verhaal-blok met wat langere tekst, optionele afbeelding en CTA-knop.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[about_enabled]" value="1" <?php checked($data['about_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[about_title]" value="<?php echo esc_attr($data['about_title']); ?>">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel <span class="tt-ed__hint">(optioneel)</span></label>
                        <input type="text" name="home[about_subtitle]" value="<?php echo esc_attr($data['about_subtitle']); ?>">
                    </div>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>
                            Tekst
                            <span class="tt-ed__hint">(lege regel = nieuwe paragraaf)</span>
                        </label>
                        <textarea name="home[about_text]" rows="8"><?php echo esc_textarea($data['about_text']); ?></textarea>
                    </div>
                    <div class="tt-ed__field">
                        <label>CTA link URL <span class="tt-ed__hint">(optioneel)</span></label>
                        <input type="url" name="home[about_cta_url]" value="<?php echo esc_attr($data['about_cta_url']); ?>" placeholder="https://...">
                    </div>
                    <div class="tt-ed__field">
                        <label>CTA knop tekst <span class="tt-ed__hint">(leeg = geen knop)</span></label>
                        <input type="text" name="home[about_cta_label]" value="<?php echo esc_attr($data['about_cta_label']); ?>" placeholder="Neem contact op">
                    </div>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>
                            Afbeelding
                            <span class="tt-ed__hint">(optioneel — rechts naast de tekst)</span>
                        </label>
                        <?php self::render_image_field('home[about_image_id]', $data['about_image_id']); ?>
                    </div>
                </div>
            </div>

            <!-- Steps (Hoe werkt het) -->
            <div class="tt-ed__group" data-section-key="steps">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('steps'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('check-circle', 20); ?> Hoe werkt het (3 stappen)</h3>
                        <p>Genummerde stappen die uitleggen hoe boeken werkt.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[steps_enabled]" value="1" <?php checked($data['steps_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[steps_title]" value="<?php echo esc_attr($data['steps_title']); ?>">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel <span class="tt-ed__hint">(optioneel)</span></label>
                        <input type="text" name="home[steps_subtitle]" value="<?php echo esc_attr($data['steps_subtitle']); ?>">
                    </div>
                </div>

                <div class="tt-ed__usps">
                    <?php foreach ($data['steps_items'] as $i => $step) : ?>
                        <div class="tt-ed__usp-row">
                            <div class="tt-ed__usp-num">Stap <?php echo $i + 1; ?></div>
                            <div class="tt-ed__usp-grid">
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Titel</label>
                                    <input type="text" name="home[steps_items][<?php echo $i; ?>][title]" value="<?php echo esc_attr($step['title']); ?>">
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Beschrijving</label>
                                    <textarea name="home[steps_items][<?php echo $i; ?>][text]" rows="3"><?php echo esc_textarea($step['text']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Spotlight — één prominente dienst-kaart -->
            <div class="tt-ed__group" data-section-key="spotlight">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('spotlight'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('star', 20); ?> Spotlight (1 prominente kaart)</h3>
                        <p>Eén brede kaart om je belangrijkste dienst extra te benadrukken. Vooral bedoeld voor de One-page-stijl.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[spotlight_enabled]" value="1" <?php checked($data['spotlight_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>Stijl</label>
                        <div class="tt-ed__hero-picker">
                            <?php
                            $spot_styles = [
                                'subtle' => ['label' => 'Subtle', 'desc' => 'Rustige highlight-kaart voor een dienst'],
                                'promo'  => ['label' => 'Promo',  'desc' => 'Accent-band met korting/aanbieding'],
                            ];
                            foreach ($spot_styles as $key => $meta) :
                                $selected = ($data['spotlight_style'] ?? 'subtle') === $key;
                            ?>
                                <label class="tt-ed__hero-opt <?php echo $selected ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="home[spotlight_style]" value="<?php echo esc_attr($key); ?>" <?php checked($selected); ?>>
                                    <div class="tt-ed__hero-opt-label"><?php echo esc_html($meta['label']); ?></div>
                                    <div class="tt-ed__hero-opt-desc"><?php echo esc_html($meta['desc']); ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="tt-ed__field tt-ed__field--icon-picker tt-ed__field--full">
                        <label>Icoon</label>
                        <div class="tt-ed__icon-picker">
                            <?php foreach (TaxiTheme_Icons::usp_choices() as $name) :
                                $selected = ($data['spotlight_icon'] ?? '') === $name;
                            ?>
                                <label class="tt-ed__icon-opt <?php echo $selected ? 'is-selected' : ''; ?>" title="<?php echo esc_attr($name); ?>">
                                    <input type="radio" name="home[spotlight_icon]" value="<?php echo esc_attr($name); ?>" <?php checked($selected); ?>>
                                    <?php echo TaxiTheme_Icons::svg($name, 20); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="tt-ed__field">
                        <label>Eyebrow <span class="tt-ed__hint">(kort, verschijnt in caps)</span></label>
                        <input type="text" name="home[spotlight_eyebrow]" value="<?php echo esc_attr($data['spotlight_eyebrow']); ?>" placeholder="Voor reizigers">
                    </div>
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[spotlight_title]" value="<?php echo esc_attr($data['spotlight_title']); ?>" placeholder="Schiphol Airport Taxi">
                    </div>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>Beschrijving</label>
                        <textarea name="home[spotlight_text]" rows="3"><?php echo esc_textarea($data['spotlight_text']); ?></textarea>
                    </div>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>Korting / Aanbieding <span class="tt-ed__hint">(alleen bij "Promo" stijl — bijv. "10%" of "€5 korting")</span></label>
                        <input type="text" name="home[spotlight_discount]" value="<?php echo esc_attr($data['spotlight_discount'] ?? ''); ?>" placeholder="10%">
                    </div>
                    <div class="tt-ed__field">
                        <label>Link URL <span class="tt-ed__hint">(optioneel)</span></label>
                        <input type="url" name="home[spotlight_link_url]" value="<?php echo esc_attr($data['spotlight_link_url']); ?>" placeholder="https://...">
                    </div>
                    <div class="tt-ed__field">
                        <label>Link tekst <span class="tt-ed__hint">(leeg = geen link)</span></label>
                        <input type="text" name="home[spotlight_link_label]" value="<?php echo esc_attr($data['spotlight_link_label']); ?>" placeholder="Meer weten">
                    </div>
                </div>
            </div>

            <!-- Features (alternating image/text rows — Premium preset) -->
            <div class="tt-ed__group" data-section-key="features">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('features'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('sparkles', 20); ?> Features (3 blokken met afbeelding)</h3>
                        <p>Grote afwisselende image/tekst secties. Vooral voor de "Premium" preset — andere presets tonen dit blok niet.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[features_enabled]" value="1" <?php checked($data['features_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>

                <div class="tt-ed__features">
                    <?php foreach ($data['features_items'] as $i => $feat) : ?>
                        <div class="tt-ed__usp-row">
                            <div class="tt-ed__usp-num">Feature <?php echo $i + 1; ?></div>
                            <div class="tt-ed__usp-grid">
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Eyebrow <span class="tt-ed__hint">(kort — bijv. "Snelheid", verschijnt als "0<?php echo $i + 1; ?> · SNELHEID")</span></label>
                                    <input type="text" name="home[features_items][<?php echo $i; ?>][eyebrow]" value="<?php echo esc_attr($feat['eyebrow'] ?? ''); ?>" placeholder="Snelheid">
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Titel</label>
                                    <input type="text" name="home[features_items][<?php echo $i; ?>][title]" value="<?php echo esc_attr($feat['title']); ?>">
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Beschrijving</label>
                                    <textarea name="home[features_items][<?php echo $i; ?>][text]" rows="4"><?php echo esc_textarea($feat['text']); ?></textarea>
                                </div>
                                <?php self::render_image_field(
                                    'home[features_items][' . $i . '][image_id]',
                                    $feat['image_id'],
                                    'Afbeelding'
                                ); ?>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Link URL <span class="tt-ed__hint">(optioneel)</span></label>
                                    <input type="url" name="home[features_items][<?php echo $i; ?>][link_url]" value="<?php echo esc_attr($feat['link_url'] ?? ''); ?>" placeholder="https://...">
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Link tekst <span class="tt-ed__hint">(leeg = geen link)</span></label>
                                    <input type="text" name="home[features_items][<?php echo $i; ?>][link_label]" value="<?php echo esc_attr($feat['link_label'] ?? ''); ?>" placeholder="Meer weten">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Diensten -->
            <div class="tt-ed__group" data-section-key="services">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('services'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('briefcase', 20); ?> Diensten (tot 6 blokken)</h3>
                        <p>Type ritten die je aanbiedt — met optionele link naar een landingspagina.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[services_enabled]" value="1" <?php checked($data['services_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[services_title]" value="<?php echo esc_attr($data['services_title']); ?>">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel</label>
                        <input type="text" name="home[services_subtitle]" value="<?php echo esc_attr($data['services_subtitle']); ?>">
                    </div>
                </div>

                <div class="tt-ed__usps">
                    <?php
                    $svc_icon_choices = TaxiTheme_Icons::usp_choices();
                    // Altijd 6 slots tonen — pad met lege entries als defaults minder hebben
                    $svc_items = array_pad($data['services_items'], 6, ['icon' => 'car', 'title' => '', 'text' => '', 'link_url' => '', 'link_label' => '', 'home_image_id' => 0, 'image_id' => 0, 'long_text' => '', 'features' => '', 'price' => '']);
                    foreach ($svc_items as $i => $svc) : ?>
                        <div class="tt-ed__usp-row">
                            <div class="tt-ed__usp-num">Dienst <?php echo $i + 1; ?></div>
                            <div class="tt-ed__usp-grid">
                                <div class="tt-ed__field tt-ed__field--icon-picker">
                                    <label>Icoon</label>
                                    <div class="tt-ed__icon-picker">
                                        <?php foreach ($svc_icon_choices as $name) :
                                            $selected = $svc['icon'] === $name;
                                        ?>
                                            <label class="tt-ed__icon-opt <?php echo $selected ? 'is-selected' : ''; ?>" title="<?php echo esc_attr($name); ?>">
                                                <input type="radio" name="home[services_items][<?php echo $i; ?>][icon]" value="<?php echo esc_attr($name); ?>" <?php checked($selected); ?>>
                                                <?php echo TaxiTheme_Icons::svg($name, 20); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Titel</label>
                                    <input type="text" name="home[services_items][<?php echo $i; ?>][title]" value="<?php echo esc_attr($svc['title']); ?>">
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Beschrijving</label>
                                    <textarea name="home[services_items][<?php echo $i; ?>][text]" rows="3"><?php echo esc_textarea($svc['text']); ?></textarea>
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Link URL <span class="tt-ed__hint">(optioneel)</span></label>
                                    <input type="url" name="home[services_items][<?php echo $i; ?>][link_url]" value="<?php echo esc_attr($svc['link_url']); ?>" placeholder="https://...">
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Link tekst <span class="tt-ed__hint">(leeg = geen link)</span></label>
                                    <input type="text" name="home[services_items][<?php echo $i; ?>][link_label]" value="<?php echo esc_attr($svc['link_label']); ?>" placeholder="Meer weten">
                                </div>

                                <?php if ($preset === 'simpel') : ?>
                                    <div class="tt-ed__field tt-ed__field--full">
                                        <label>Afbeelding voor homepagekaart <span class="tt-ed__hint">(optioneel — leeg = icoon getoond)</span></label>
                                        <?php self::render_image_field('home[services_items][' . $i . '][home_image_id]', $svc['home_image_id'] ?? 0); ?>
                                    </div>
                                <?php else :
                                    // Preserve waarde bij save vanuit andere presets
                                    echo '<input type="hidden" name="home[services_items][' . $i . '][home_image_id]" value="' . (int) ($svc['home_image_id'] ?? 0) . '">';
                                endif; ?>

                                <?php
                                // Detail-velden worden bewerkt op de Diensten-page editor.
                                // Hier als hidden om waarden te bewaren bij save vanuit homepage-editor.
                                // Multi-line velden via <textarea style="display:none"> ipv hidden input om newlines te preserveren.
                                ?>
                                <?php // Diensten-page detail-velden — bewerkt daar, hier bewaard ?>
                                <input type="hidden" name="home[services_items][<?php echo $i; ?>][image_id]"  value="<?php echo (int) ($svc['image_id']  ?? 0); ?>">
                                <input type="hidden" name="home[services_items][<?php echo $i; ?>][price]"     value="<?php echo esc_attr($svc['price']     ?? ''); ?>">
                                <textarea name="home[services_items][<?php echo $i; ?>][long_text]" style="display:none;"><?php echo esc_textarea($svc['long_text'] ?? ''); ?></textarea>
                                <textarea name="home[services_items][<?php echo $i; ?>][features]"  style="display:none;"><?php echo esc_textarea($svc['features']  ?? ''); ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="tt-ed__toggle-row" style="margin-top:16px;">
                    <label class="tt-ed__toggle-inline">
                        <input type="checkbox" name="home[services_link_enabled]" value="1" <?php checked($data['services_link_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                        <span class="tt-ed__toggle-label">"Lees meer" knop op cards</span>
                    </label>
                    <span class="tt-ed__hint">(zichtbaar in preset "Simpel" — link naar Diensten-pagina)</span>
                </div>
            </div>

            <!-- Waarom ons -->
            <div class="tt-ed__group" data-section-key="waarom">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('waarom'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('check-circle', 20); ?> Waarom ons</h3>
                        <p>Checklist met redenen om voor jouw bedrijf te kiezen.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[waarom_enabled]" value="1" <?php checked($data['waarom_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[waarom_title]" value="<?php echo esc_attr($data['waarom_title']); ?>">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel</label>
                        <input type="text" name="home[waarom_subtitle]" value="<?php echo esc_attr($data['waarom_subtitle']); ?>">
                    </div>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>
                            Checklist items
                            <span class="tt-ed__hint">(één per regel — lege regels worden overgeslagen)</span>
                        </label>
                        <textarea name="home[waarom_items_raw]" rows="7"><?php echo esc_textarea(implode("\n", $data['waarom_items'])); ?></textarea>
                    </div>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>
                            Afbeelding
                            <span class="tt-ed__hint">(optioneel — indien geüpload verschijnt 'ie naast de checklist)</span>
                        </label>
                        <?php self::render_image_field('home[waarom_image_id]', $data['waarom_image_id']); ?>
                    </div>
                </div>
            </div>

            <!-- Service gebied -->
            <div class="tt-ed__group" data-section-key="service-area">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('service-area'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('map-pin', 20); ?> Service gebied</h3>
                        <p>Grid met plaatsen/regio's waar jouw taxibedrijf actief is.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[service_area_enabled]" value="1" <?php checked($data['service_area_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[service_area_title]" value="<?php echo esc_attr($data['service_area_title']); ?>">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel</label>
                        <input type="text" name="home[service_area_subtitle]" value="<?php echo esc_attr($data['service_area_subtitle']); ?>">
                    </div>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>
                            Plaatsen
                            <span class="tt-ed__hint">(vul in wat je wil tonen — lege slots worden overgeslagen)</span>
                        </label>
                        <div class="tt-ed__cities">
                            <?php for ($i = 0; $i < 16; $i++) :
                                $val = $data['service_area_items'][$i] ?? '';
                            ?>
                                <input type="text" name="home[service_area_items][<?php echo $i; ?>]" value="<?php echo esc_attr($val); ?>" placeholder="bv. Amsterdam">
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vaste route prijzen -->
            <div class="tt-ed__group" data-section-key="routes">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('routes'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('euro', 20); ?> Vaste route prijzen</h3>
                        <p>Populaire ritten met vaste prijs (bv. Amsterdam → Schiphol).</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[routes_enabled]" value="1" <?php checked($data['routes_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[routes_title]" value="<?php echo esc_attr($data['routes_title']); ?>">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel</label>
                        <input type="text" name="home[routes_subtitle]" value="<?php echo esc_attr($data['routes_subtitle']); ?>">
                    </div>
                </div>
                <div class="tt-ed__routes-cols" style="margin-top:16px;">
                    <div class="tt-ed__routes">
                        <?php for ($i = 0; $i < 6; $i++) :
                            $r = $data['routes_items'][$i] ?? ['from' => '', 'to' => '', 'price' => ''];
                        ?>
                            <div class="tt-ed__route-row">
                                <span class="tt-ed__route-num"><?php echo $i + 1; ?></span>
                                <input type="text" name="home[routes_items][<?php echo $i; ?>][from]" value="<?php echo esc_attr($r['from']); ?>" placeholder="Ophaal">
                                <span class="tt-ed__route-arrow">→</span>
                                <input type="text" name="home[routes_items][<?php echo $i; ?>][to]" value="<?php echo esc_attr($r['to']); ?>" placeholder="Bestemming">
                                <div class="tt-ed__price-wrap">
                                    <span class="tt-ed__price-prefix">€</span>
                                    <input type="text" name="home[routes_items][<?php echo $i; ?>][price]" value="<?php echo esc_attr($r['price']); ?>" placeholder="45">
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="tt-ed__routes">
                        <?php for ($i = 6; $i < 12; $i++) :
                            $r = $data['routes_items'][$i] ?? ['from' => '', 'to' => '', 'price' => ''];
                        ?>
                            <div class="tt-ed__route-row">
                                <span class="tt-ed__route-num"><?php echo $i + 1; ?></span>
                                <input type="text" name="home[routes_items][<?php echo $i; ?>][from]" value="<?php echo esc_attr($r['from']); ?>" placeholder="Ophaal">
                                <span class="tt-ed__route-arrow">→</span>
                                <input type="text" name="home[routes_items][<?php echo $i; ?>][to]" value="<?php echo esc_attr($r['to']); ?>" placeholder="Bestemming">
                                <div class="tt-ed__price-wrap">
                                    <span class="tt-ed__price-prefix">€</span>
                                    <input type="text" name="home[routes_items][<?php echo $i; ?>][price]" value="<?php echo esc_attr($r['price']); ?>" placeholder="45">
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- FAQ -->
            <?php
            $faq_page_id  = TaxiTheme_Installer::get_page_id('faq');
            $faq_edit_url = $faq_page_id ? admin_url('post.php?post=' . $faq_page_id . '&action=edit') : '';
            ?>
            <div class="tt-ed__group" data-section-key="faq">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('faq'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('info', 20); ?> FAQ (homepage — 5 vragen)</h3>
                        <p>Deze 5 vragen verschijnen op de homepage. Voor extra vragen: beheer die op de FAQ-pagina zelf.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[faq_enabled]" value="1" <?php checked($data['faq_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[faq_title]" value="<?php echo esc_attr($data['faq_title']); ?>">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel <span class="tt-ed__hint">(optioneel)</span></label>
                        <input type="text" name="home[faq_subtitle]" value="<?php echo esc_attr($data['faq_subtitle']); ?>">
                    </div>
                </div>

                <?php
                // Hidden: het aantal getoonde vragen op home is nu vast op alle
                // ingevulde slots (max 5). Wordt via faq_home_limit=5 opgeslagen.
                ?>
                <input type="hidden" name="home[faq_home_limit]" value="5">

                <div class="tt-ed__reviews-list">
                    <?php foreach ($data['faq_items'] as $i => $faq) : ?>
                        <div class="tt-ed__usp-row">
                            <div class="tt-ed__usp-num">Vraag <?php echo $i + 1; ?></div>
                            <div class="tt-ed__usp-grid">
                                <div class="tt-ed__field">
                                    <label>Vraag</label>
                                    <input type="text" name="home[faq_items][<?php echo $i; ?>][question]" value="<?php echo esc_attr($faq['question']); ?>" placeholder="Bijv. Hoe kan ik betalen?">
                                </div>
                                <div class="tt-ed__field">
                                    <label>Antwoord</label>
                                    <textarea name="home[faq_items][<?php echo $i; ?>][answer]" rows="3" placeholder="Kort en duidelijk antwoord"><?php echo esc_textarea($faq['answer']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($faq_edit_url) : ?>
                    <div class="tt-ed__faq-more">
                        <a href="<?php echo esc_url($faq_edit_url); ?>" class="tt-ed__faq-more-link">
                            Meer FAQ vragen beheren op de FAQ-pagina
                            <?php echo TaxiTheme_Icons::svg('arrow-right', 14); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Fleet (Ons wagenpark) -->
            <div class="tt-ed__group" data-section-key="fleet">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('fleet'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('car', 20); ?> Ons wagenpark (5 slots)</h3>
                        <p>Mozaïek-grid van je vloot: 1 grote foto links, 1 brede rechtsboven, 2 vierkante rechtsonder, 1 brede feature-card onderaan. Elke card kan alleen een tag hebben, of een grote titel + subtitle als "feature" overlay.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[fleet_enabled]" value="1" <?php checked($data['fleet_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[fleet_title]" value="<?php echo esc_attr($data['fleet_title']); ?>">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel</label>
                        <input type="text" name="home[fleet_subtitle]" value="<?php echo esc_attr($data['fleet_subtitle']); ?>">
                    </div>
                </div>

                <div class="tt-ed__fleet-list">
                    <?php
                    $fleet_positions = [
                        1 => 'Groot (linksboven, tall)',
                        2 => 'Breed (rechtsboven)',
                        3 => 'Vierkant (midden)',
                        4 => 'Vierkant (rechts)',
                        5 => 'Feature (onderaan, full-width)',
                    ];
                    foreach ($data['fleet_items'] as $i => $item) :
                        $pos_label = $fleet_positions[$i + 1] ?? 'Slot ' . ($i + 1);
                    ?>
                        <div class="tt-ed__usp-row">
                            <div class="tt-ed__usp-num">Slot <?php echo $i + 1; ?> · <?php echo esc_html($pos_label); ?></div>
                            <div class="tt-ed__usp-grid">
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Afbeelding</label>
                                    <?php self::render_image_field('home[fleet_items][' . $i . '][image_id]', (int) ($item['image_id'] ?? 0)); ?>
                                </div>
                                <div class="tt-ed__field">
                                    <label>Tag <span class="tt-ed__hint">(kleine pill linksonder)</span></label>
                                    <input type="text" name="home[fleet_items][<?php echo $i; ?>][tag]" value="<?php echo esc_attr($item['tag']); ?>" placeholder="Bijv. Mercedes E-Klasse">
                                </div>
                                <div class="tt-ed__field">
                                    <label>Feature-titel <span class="tt-ed__hint">(optioneel — overschrijft de tag)</span></label>
                                    <input type="text" name="home[fleet_items][<?php echo $i; ?>][title]" value="<?php echo esc_attr($item['title']); ?>" placeholder="Bijv. Mercedes-Benz vloot">
                                </div>
                                <div class="tt-ed__field tt-ed__field--full">
                                    <label>Feature-subtitle</label>
                                    <input type="text" name="home[fleet_items][<?php echo $i; ?>][subtitle]" value="<?php echo esc_attr($item['subtitle']); ?>" placeholder="Bijv. Comfort en luxe voor elke rit">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Reviews -->
            <div class="tt-ed__group" data-section-key="reviews">
                <div class="tt-ed__group-head">
                    <?php self::render_section_order_controls('reviews'); ?>
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('star', 20); ?> Reviews (max 6)</h3>
                        <p>Handmatig ingevulde klantreviews met sterren. Optioneel CTA-link naar je Google Reviews page onderaan.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[reviews_enabled]" value="1" <?php checked($data['reviews_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[reviews_title]" value="<?php echo esc_attr($data['reviews_title']); ?>">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel <span class="tt-ed__hint">(optioneel)</span></label>
                        <input type="text" name="home[reviews_subtitle]" value="<?php echo esc_attr($data['reviews_subtitle']); ?>">
                    </div>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>Google Reviews URL <span class="tt-ed__hint">(bv. https://g.page/r/... — leeg = geen CTA-knop)</span></label>
                        <input type="url" name="home[reviews_google_url]" value="<?php echo esc_attr($data['reviews_google_url']); ?>" placeholder="https://g.page/r/...">
                    </div>
                    <div class="tt-ed__field tt-ed__field--full">
                        <label>CTA-tekst</label>
                        <input type="text" name="home[reviews_cta_label]" value="<?php echo esc_attr($data['reviews_cta_label']); ?>" placeholder="Bekijk alle reviews op Google">
                    </div>
                </div>

                <div class="tt-ed__reviews-list">
                    <?php foreach ($data['reviews_items'] as $i => $review) : ?>
                        <div class="tt-ed__usp-row">
                            <div class="tt-ed__usp-num">Review <?php echo $i + 1; ?></div>
                            <div class="tt-ed__usp-grid">
                                <div class="tt-ed__field">
                                    <label>Naam</label>
                                    <input type="text" name="home[reviews_items][<?php echo $i; ?>][name]" value="<?php echo esc_attr($review['name']); ?>" placeholder="Bijv. Sander K.">
                                </div>
                                <div class="tt-ed__field">
                                    <label>Sterren</label>
                                    <select name="home[reviews_items][<?php echo $i; ?>][rating]">
                                        <?php for ($s = 5; $s >= 1; $s--) : ?>
                                            <option value="<?php echo $s; ?>" <?php selected((int) $review['rating'], $s); ?>><?php echo str_repeat('★', $s) . str_repeat('☆', 5 - $s); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="tt-ed__field">
                                    <label>Datum <span class="tt-ed__hint">(optioneel)</span></label>
                                    <input type="text" name="home[reviews_items][<?php echo $i; ?>][date]" value="<?php echo esc_attr($review['date']); ?>" placeholder="Bijv. maart 2025">
                                </div>
                                <div class="tt-ed__field">
                                    <label>Review-tekst</label>
                                    <textarea name="home[reviews_items][<?php echo $i; ?>][text]" rows="3" placeholder="Wat de klant zei"><?php echo esc_textarea($review['text']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Contact CTA -->
            <div class="tt-ed__group" id="tt-ed-section-contact-cta">
                <div class="tt-ed__group-head">
                    <div class="tt-ed__group-head-text">
                        <h3><?php echo TaxiTheme_Icons::svg('phone', 20); ?> Contact CTA</h3>
                        <p>Onderste sectie met bel/mail knoppen. Gegevens uit Bedrijfsgegevens.</p>
                    </div>
                    <label class="tt-ed__toggle">
                        <input type="checkbox" name="home[contact_enabled]" value="1" <?php checked($data['contact_enabled'], 1); ?>>
                        <span class="tt-ed__toggle-track"><span class="tt-ed__toggle-thumb"></span></span>
                    </label>
                </div>
                <div class="tt-ed__grid">
                    <div class="tt-ed__field">
                        <label>Titel</label>
                        <input type="text" name="home[contact_title]" value="<?php echo esc_attr($data['contact_title']); ?>">
                    </div>
                    <div class="tt-ed__field">
                        <label>Ondertitel</label>
                        <input type="text" name="home[contact_subtitle]" value="<?php echo esc_attr($data['contact_subtitle']); ?>">
                    </div>
                </div>
            </div>

                </div><!-- /.tt-ed__main -->
            </div><!-- /.tt-ed__layout -->

            <div class="tt-ed__actions">
                <button type="submit" class="tt-ed__btn tt-ed__btn--primary">Wijzigingen opslaan</button>
                <?php if (self::$saved) : ?>
                    <span class="tt-ed__saved-indicator" data-auto-hide="1">✓ Wijzigingen opgeslagen</span>
                <?php endif; ?>
            </div>
        </form>
        <?php
        self::render_image_picker_js();
    }

    private static function render_hero_mock($style) {
        switch ($style) {
            case 'split':
                echo '<div class="mk__row"><div class="mk__col"><div class="mk__bar mk__bar--sm"></div><div class="mk__bar mk__bar--lg"></div><div class="mk__bar mk__bar--md"></div><div class="mk__btn"></div></div><div class="mk__col"><div class="mk__form"></div></div></div>';
                break;
            case 'stacked':
                echo '<div class="mk__stack"><div class="mk__bar mk__bar--lg mk__center"></div><div class="mk__bar mk__bar--md mk__center"></div><div class="mk__form mk__form--wide"></div></div>';
                break;
            case 'centered':
                echo '<div class="mk__stack"><div class="mk__bar mk__bar--sm mk__center"></div><div class="mk__bar mk__bar--lg mk__center"></div><div class="mk__bar mk__bar--md mk__center"></div><div class="mk__btns"><div class="mk__btn"></div><div class="mk__btn mk__btn--ghost"></div></div></div>';
                break;
        }
    }

    private static function render_styles() {
        ?>
        <style>
            #wpcontent, #wpbody-content { padding: 0 !important; }
            .auto-fold #wpcontent { margin-left: 36px; }
            @media (min-width: 961px) { .auto-fold #wpcontent { margin-left: 160px; } }
            #wpfooter, .update-nag, .notice, div.error, div.updated { display: none !important; }

            .tt-ed {
                font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
                padding: 32px 24px 60px;
                color: #14161f;
                width: 100%;
            }
            .tt-ed * { box-sizing: border-box; }

            .tt-ed__topbar {
                display: grid;
                grid-template-columns: 1fr auto 1fr;
                align-items: center;
                gap: 20px;
                margin-bottom: 24px;
            }
            .tt-ed__back {
                justify-self: start;
                font-size: 0.9rem;
                font-weight: 600;
                color: #6b7280;
                text-decoration: none;
            }
            .tt-ed__back:hover { color: #14161f; }
            .tt-ed__title-block { text-align: center; }
            .tt-ed__title {
                font-size: 1.6rem;
                font-weight: 800;
                margin: 0;
                letter-spacing: -0.025em;
            }
            .tt-ed__subtitle {
                max-width: 620px;
                margin: 7px auto 0;
                color: #6b7280;
                font-size: 0.86rem;
                line-height: 1.5;
            }
            .tt-ed__view {
                justify-self: end;
                font-size: 0.9rem;
                font-weight: 600;
                padding: 8px 16px;
                background: transparent;
                color: #6b7280;
                border: 1.5px solid #e6e2d5;
                border-radius: 8px;
                text-decoration: none;
            }
            .tt-ed__view:hover { color: #14161f; border-color: #14161f; }

            /* Saved indicator — vervangt de save-button na een succesvolle save */
            .tt-ed__saved-indicator {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #059669;
                color: #fff;
                padding: 12px 22px;
                border-radius: 8px;
                font-weight: 700;
                font-size: 0.95rem;
                animation: tt-ed-saved-in 0.3s ease;
            }
            @keyframes tt-ed-saved-in {
                from { opacity: 0; transform: translateY(6px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            /* Groups → each section a separate card */
            .tt-ed__group {
                background: #fff;
                border: 1px solid #e6e2d5;
                border-radius: 14px;
                padding: 28px 32px;
                margin-bottom: 16px;
                box-shadow: 0 2px 8px -4px rgba(20, 22, 31, 0.06);
                transition: box-shadow 0.15s;
                scroll-margin-top: 32px;
            }
            .tt-ed__group:hover { box-shadow: 0 6px 20px -8px rgba(20, 22, 31, 0.12); }
            .tt-ed__form--component-mode .tt-ed__component-view { display: none; }
            .tt-ed__form--component-mode .tt-ed__component-view.is-active-view {
                display: block;
                animation: tt-ed-view-in 0.2s ease;
            }
            @keyframes tt-ed-view-in {
                from { opacity: 0; transform: translateY(5px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .tt-ed__group-head {
                display: grid;
                grid-template-columns: 1fr auto;
                gap: 16px;
                align-items: start;
                margin-bottom: 24px;
                padding-bottom: 20px;
                border-bottom: 1px solid #f0ede2;
            }
            .tt-ed__group-head-text { min-width: 0; }
            .tt-ed__group-head h3 {
                font-size: 1.15rem;
                font-weight: 700;
                margin: 0 0 4px;
                display: inline-flex;
                align-items: center;
                gap: 10px;
            }
            .tt-ed__group-head h3 svg { color: #c17d00; flex-shrink: 0; }
            .tt-ed__group-head p {
                font-size: 0.9rem;
                color: #6b7280;
                margin: 0;
            }

            /* Semantische achtergrondkeuze per homepagecomponent */
            .tt-ed__background-setting {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                margin: -8px 0 24px;
                padding: 14px 16px;
                background: #f8fafc;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
            }
            .tt-ed__background-intro {
                display: flex;
                flex-direction: column;
                gap: 2px;
                min-width: 150px;
            }
            .tt-ed__background-intro strong {
                color: #111827;
                font-size: 0.88rem;
            }
            .tt-ed__background-intro span {
                color: #6b7280;
                font-size: 0.75rem;
            }
            .tt-ed__background-choices {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-end;
                gap: 7px;
            }
            .tt-ed__background-choice {
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                min-height: 36px;
                padding: 6px 10px 6px 7px;
                background: #fff;
                border: 1.5px solid #dbe1e8;
                border-radius: 8px;
                color: #374151;
                cursor: pointer;
                font-size: 0.78rem;
                font-weight: 650;
                transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
            }
            .tt-ed__background-choice:hover {
                border-color: #9ca3af;
            }
            .tt-ed__background-choice:has(input:checked) {
                background: #fffbeb;
                border-color: #f5b800;
                box-shadow: 0 0 0 2px rgba(245, 184, 0, 0.14);
            }
            .tt-ed__background-choice input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .tt-ed__background-swatch {
                width: 21px;
                height: 21px;
                flex: 0 0 auto;
                border: 1px solid rgba(15, 23, 42, 0.18);
                border-radius: 6px;
                box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
            }
            .tt-ed__background-label { white-space: nowrap; }
            @media (max-width: 820px) {
                .tt-ed__background-setting {
                    align-items: flex-start;
                    flex-direction: column;
                }
                .tt-ed__background-choices { justify-content: flex-start; }
            }

            /* Form fields */
            .tt-ed__grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px 20px;
            }
            .tt-ed__field { display: flex; flex-direction: column; }
            .tt-ed__field--full { grid-column: 1 / -1; }
            /* WYSIWYG wrap voor privacy/voorwaarden — TinyMCE past zich aan de container */
            .tt-ed__wysiwyg-wrap { padding: 12px 0; }
            .tt-ed__wysiwyg-wrap .wp-editor-container {
                border: 1px solid #e6e2d5;
                border-radius: 8px;
                overflow: hidden;
            }
            .tt-ed__wysiwyg-wrap .mce-toolbar-grp,
            .tt-ed__wysiwyg-wrap .quicktags-toolbar {
                background: #fdfcf7 !important;
            }
            .tt-ed__wysiwyg-wrap .wp-editor-area {
                font-family: inherit;
                font-size: 0.95rem;
                line-height: 1.65;
            }
            .tt-ed__field label {
                font-size: 0.9rem;
                font-weight: 600;
                margin-bottom: 6px;
            }
            .tt-ed__field input,
            .tt-ed__field textarea {
                padding: 11px 14px;
                border: 1.5px solid #e6e2d5;
                border-radius: 8px;
                font-size: 0.95rem;
                font-family: inherit;
                background: #fff;
                transition: border-color 0.15s, box-shadow 0.15s;
            }
            .tt-ed__field textarea { resize: vertical; min-height: 60px; }
            .tt-ed__field input:focus,
            .tt-ed__field textarea:focus {
                outline: none;
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }
            .tt-ed__hint {
                color: #9ca3af;
                font-weight: 500;
                font-size: 0.8rem;
                margin-left: 6px;
            }

            /* Icon picker (radio buttons als tegels) */
            .tt-ed__field--icon-picker { display: flex; flex-direction: column; }
            .tt-ed__icon-picker {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(46px, 1fr));
                gap: 8px;
            }
            .tt-ed__icon-opt {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                aspect-ratio: 1 / 1;
                background: #fff;
                border: 1.5px solid #e6e2d5;
                border-radius: 8px;
                cursor: pointer;
                color: #6b7280;
                transition: all 0.15s;
            }
            .tt-ed__icon-opt input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .tt-ed__icon-opt:hover { border-color: #14161f; color: #14161f; }
            .tt-ed__icon-opt:has(input:checked),
            .tt-ed__icon-opt.is-selected {
                background: #14161f;
                border-color: #14161f;
                color: #f5b800;
            }

            /* Hero picker (large radio cards with schematic previews) */
            .tt-ed__hero-picker {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
            }
            .tt-ed__hero-opt {
                position: relative;
                display: flex;
                flex-direction: column;
                gap: 8px;
                padding: 12px;
                background: #fff;
                border: 2px solid #e6e2d5;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.15s;
            }
            .tt-ed__hero-opt input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .tt-ed__hero-opt:hover { border-color: #14161f; }
            .tt-ed__hero-opt:has(input:checked),
            .tt-ed__hero-opt.is-selected {
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.15);
            }
            .tt-ed__hero-opt-label { font-weight: 700; font-size: 0.9rem; }
            .tt-ed__hero-opt-desc { font-size: 0.8rem; color: #6b7280; }

            /* Mock previews */
            .tt-ed__hero-mock {
                background: linear-gradient(135deg, #14161f, #1e2233);
                border-radius: 6px;
                padding: 14px;
                height: 90px;
                display: flex;
                align-items: center;
                overflow: hidden;
            }
            .mk__col--image { position: relative; display: flex; align-items: center; justify-content: center; }
            .mk__col--image .mk__form {
                position: relative;
                z-index: 1;
                min-height: 40px;
                width: 90%;
            }
            .mk__yellow-shape {
                position: absolute;
                top: -6px; right: -4px;
                width: 90%; height: 90%;
                background: #f5b800;
                transform: skewY(-6deg);
                border-radius: 3px;
                z-index: 0;
            }
            .mk__row { display: flex; gap: 10px; width: 100%; align-items: center; }
            .mk__col { flex: 1; display: flex; flex-direction: column; gap: 4px; }
            .mk__stack { display: flex; flex-direction: column; gap: 5px; width: 100%; align-items: stretch; }
            .mk__stack--tight { gap: 4px; }
            .mk__bar { height: 4px; border-radius: 2px; background: rgba(255, 255, 255, 0.35); }
            .mk__bar--sm { width: 30%; height: 3px; background: rgba(245, 184, 0, 0.7); }
            .mk__bar--md { width: 60%; }
            .mk__bar--lg { width: 90%; height: 6px; background: rgba(255, 255, 255, 0.7); }
            .mk__center { margin-left: auto; margin-right: auto; }
            .mk__btn {
                width: 40px; height: 8px;
                background: #f5b800;
                border-radius: 3px;
                margin-top: 4px;
            }
            .mk__btn--ghost { background: transparent; border: 1px solid rgba(255,255,255,0.5); }
            .mk__btns { display: flex; gap: 4px; justify-content: center; margin-top: 4px; }
            .mk__center-block { margin: 4px auto 0; }
            .mk__form {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 4px;
                height: 100%;
                min-height: 60px;
            }
            .mk__form--wide { width: 80%; margin: 0 auto; height: 40px; }

            /* Bullet style picker (list vs tags) */
            .tt-ed__bullet-style {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-bottom: 14px;
            }
            .tt-ed__bs-opt {
                position: relative;
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 12px;
                background: #fff;
                border: 2px solid #e6e2d5;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.15s;
            }
            .tt-ed__bs-opt input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .tt-ed__bs-opt:hover { border-color: #14161f; }
            .tt-ed__bs-opt:has(input:checked),
            .tt-ed__bs-opt.is-selected {
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.15);
            }
            .tt-ed__bs-mock {
                width: 60px; height: 42px;
                background: linear-gradient(135deg, #14161f, #1e2233);
                border-radius: 5px;
                padding: 6px;
                display: flex;
                gap: 3px;
                flex-shrink: 0;
                align-items: center;
                justify-content: center;
            }
            .tt-ed__bs-mock--list { flex-direction: column; }
            .tt-ed__bs-mock--tags { flex-wrap: wrap; align-content: center; }
            .bs-line { display: flex; align-items: center; gap: 3px; width: 100%; }
            .bs-dot { width: 5px; height: 5px; border-radius: 50%; background: #f5b800; flex-shrink: 0; }
            .bs-bar { flex: 1; height: 2px; border-radius: 1px; background: rgba(255,255,255,0.5); }
            .bs-pill { display: block; width: 16px; height: 5px; border-radius: 999px; background: rgba(255,255,255,0.3); border: 1px solid rgba(255,255,255,0.5); }
            .tt-ed__bs-info { display: flex; flex-direction: column; }
            .tt-ed__bs-info strong { font-size: 0.9rem; font-weight: 700; }
            .tt-ed__bs-info small { font-size: 0.75rem; color: #6b7280; }

            /* Trust rows */
            .tt-ed__trust { display: flex; flex-direction: column; gap: 10px; }
            .tt-ed__trust-row {
                display: grid;
                grid-template-columns: 80px 140px 1fr;
                gap: 12px;
                align-items: end;
                padding: 14px;
                background: #fdfcf7;
                border: 1px solid #f0ede2;
                border-radius: 10px;
            }
            .tt-ed__trust-num {
                background: #14161f;
                color: #fff;
                padding: 4px 10px;
                border-radius: 5px;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.03em;
                white-space: nowrap;
                justify-self: start;
                margin-bottom: 8px;
            }
            .tt-ed__field--grow { min-width: 0; }
            .tt-ed__select {
                padding: 10px 12px;
                border: 1.5px solid #e6e2d5;
                border-radius: 8px;
                font-size: 0.92rem;
                font-family: inherit;
                background: #fff;
                cursor: pointer;
            }
            .tt-ed__select:focus {
                outline: none;
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }
            @media (max-width: 700px) {
                .tt-ed__trust-row { grid-template-columns: 1fr; }
                .tt-ed__bullet-style { grid-template-columns: 1fr; }
            }

            /* Route rows — from > to + prijs */
            .tt-ed__routes { display: flex; flex-direction: column; gap: 8px; }
            .tt-ed__routes-cols {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            @media (max-width: 900px) {
                .tt-ed__routes-cols { grid-template-columns: 1fr; }
            }
            .tt-ed__route-row {
                display: grid;
                grid-template-columns: 28px 1fr 20px 1fr 110px;
                gap: 10px;
                align-items: center;
                padding: 10px 14px;
                background: #fdfcf7;
                border: 1px solid #f0ede2;
                border-radius: 10px;
            }
            .tt-ed__route-num {
                width: 28px;
                height: 28px;
                background: #14161f;
                color: #f5b800;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                font-size: 0.8rem;
                flex-shrink: 0;
            }
            .tt-ed__route-arrow {
                color: #9ca3af;
                font-weight: 700;
                text-align: center;
            }
            .tt-ed__route-row input {
                padding: 9px 12px;
                border: 1.5px solid #e6e2d5;
                border-radius: 7px;
                font-size: 0.9rem;
                font-family: inherit;
                background: #fff;
                min-width: 0;
                transition: border-color 0.15s, box-shadow 0.15s;
            }
            .tt-ed__route-row input:focus {
                outline: none;
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }
            .tt-ed__route-row input:placeholder-shown { background: #fdfcf7; border-style: dashed; }
            .tt-ed__price-wrap {
                position: relative;
                display: flex;
                align-items: center;
            }
            .tt-ed__price-prefix {
                position: absolute;
                left: 12px;
                color: #6b7280;
                font-weight: 700;
                pointer-events: none;
            }
            .tt-ed__price-wrap input {
                width: 100%;
                padding-left: 26px;
                font-weight: 700;
            }
            @media (max-width: 700px) {
                .tt-ed__route-row {
                    grid-template-columns: 28px 1fr;
                    gap: 8px;
                }
                .tt-ed__route-arrow { grid-column: 1 / -1; text-align: left; }
                .tt-ed__price-wrap { grid-column: 1 / -1; }
            }

            /* Preset-notitie bovenaan een groep */
            .tt-ed__preset-note {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #fef9c3;
                border: 1px solid #fde68a;
                color: #78350f;
                padding: 8px 14px;
                border-radius: 8px;
                font-size: 0.82rem;
                font-weight: 500;
                margin-bottom: 20px;
            }
            .tt-ed__preset-note strong {
                font-weight: 700;
            }
            .tt-ed__preset-note svg {
                flex-shrink: 0;
                color: #d97706;
            }

            /* Image-upload veld */
            .tt-ed__image-field {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .tt-ed__image-preview {
                width: 100%;
                min-height: 160px;
                background: #fdfcf7;
                border: 1.5px dashed #e6e2d5;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .tt-ed__image-field.has-image .tt-ed__image-preview {
                border-style: solid;
                background: #f0ede2;
            }
            .tt-ed__image-preview img {
                max-width: 100%;
                max-height: 240px;
                display: block;
                border-radius: 8px;
            }
            .tt-ed__image-placeholder {
                color: #9ca3af;
                font-size: 0.9rem;
                font-weight: 500;
            }
            .tt-ed__image-actions {
                display: flex;
                gap: 8px;
            }

            /* Collapsible "extra fields" per service (details/summary) */
            .tt-ed__extra-fields {
                margin-top: 6px;
                border: 1px solid #f0ede2;
                border-radius: 8px;
                background: #fff;
            }
            .tt-ed__extra-fields > summary {
                padding: 10px 14px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.85rem;
                color: #6b7280;
                list-style: none;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .tt-ed__extra-fields > summary::-webkit-details-marker { display: none; }
            .tt-ed__extra-fields > summary::before {
                content: "▸";
                color: #9ca3af;
                font-size: 0.8rem;
                transition: transform 0.15s;
            }
            .tt-ed__extra-fields[open] > summary::before { transform: rotate(90deg); }
            .tt-ed__extra-fields[open] > summary { color: #14161f; border-bottom: 1px solid #f0ede2; }
            .tt-ed__extra-fields-inner {
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            /* FAQ editor rijen */
            .tt-ed__faq-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .tt-ed__faq-row {
                display: grid;
                grid-template-columns: 90px 1fr;
                gap: 14px;
                padding: 16px 18px;
                background: #fdfcf7;
                border: 1px solid #f0ede2;
                border-radius: 10px;
            }
            .tt-ed__faq-num {
                background: #14161f;
                color: #fff;
                padding: 4px 10px;
                border-radius: 5px;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.03em;
                white-space: nowrap;
                height: fit-content;
            }
            .tt-ed__faq-fields {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            @media (max-width: 700px) {
                .tt-ed__faq-row { grid-template-columns: 1fr; }
            }
            .tt-ed__faq-more {
                margin-top: 14px;
                padding-top: 14px;
                border-top: 1px dashed #e6e2d5;
            }
            .tt-ed__faq-more-link {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #14161f;
                background: #fdfcf7;
                border: 1px solid #f0ede2;
                border-radius: 8px;
                padding: 10px 16px;
                font-weight: 600;
                font-size: 0.9rem;
                text-decoration: none;
                transition: background 0.15s, border-color 0.15s;
            }
            .tt-ed__faq-more-link:hover {
                background: #f5f1e2;
                border-color: #dfd6b8;
                color: #14161f;
            }
            .tt-ed__faq-more-link svg { color: currentColor; }

            /* Cities grid — 2 kolommen inputs */
            .tt-ed__cities {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            .tt-ed__cities input {
                padding: 10px 12px;
                border: 1.5px solid #e6e2d5;
                border-radius: 8px;
                font-size: 0.92rem;
                font-family: inherit;
                background: #fff;
                transition: border-color 0.15s, box-shadow 0.15s;
            }
            .tt-ed__cities input:focus {
                outline: none;
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }
            .tt-ed__cities input:placeholder-shown {
                background: #fdfcf7;
                border-style: dashed;
            }

            /* Bullet rows — genummerde velden met duidelijke scheiding */
            .tt-ed__bullets {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .tt-ed__bullet-row {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .tt-ed__bullet-num {
                width: 28px;
                height: 28px;
                background: #f5b800;
                color: #14161f;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                font-size: 0.8rem;
                flex-shrink: 0;
            }
            .tt-ed__bullet-num--tag {
                background: rgba(20, 22, 31, 0.85);
                color: #f5b800;
                border-radius: 999px;
                padding: 0 10px;
                width: auto;
                min-width: 32px;
                font-size: 0.72rem;
            }
            .tt-ed__bullet-row input {
                flex: 1;
                padding: 10px 12px;
                border: 1.5px solid #e6e2d5;
                border-radius: 8px;
                font-size: 0.92rem;
                font-family: inherit;
                background: #fff;
                transition: border-color 0.15s, box-shadow 0.15s;
            }
            .tt-ed__bullet-row input:focus {
                outline: none;
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }
            .tt-ed__bullet-row input:placeholder-shown {
                background: #fdfcf7;
                border-style: dashed;
            }

            /* Toggle rows — inline toggle + input (voor hero fields) */
            .tt-ed__toggle-row {
                display: grid;
                grid-template-columns: 160px 1fr;
                gap: 16px;
                align-items: center;
                padding: 12px 0;
                border-bottom: 1px dashed #f0ede2;
            }
            .tt-ed__toggle-row:last-of-type { border-bottom: none; }
            .tt-ed__toggle-row--single { grid-template-columns: 160px auto; }
            .tt-ed__toggle-row--stacked { align-items: start; }
            .tt-ed__toggle-row input[type="text"],
            .tt-ed__toggle-row textarea {
                padding: 10px 12px;
                border: 1.5px solid #e6e2d5;
                border-radius: 8px;
                font-size: 0.92rem;
                font-family: inherit;
                background: #fff;
                transition: border-color 0.15s, box-shadow 0.15s;
            }
            .tt-ed__toggle-row textarea { resize: vertical; min-height: 90px; }
            .tt-ed__toggle-row input[type="text"]:focus,
            .tt-ed__toggle-row textarea:focus {
                outline: none;
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.2);
            }

            .tt-ed__toggle-inline {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
                user-select: none;
            }
            .tt-ed__toggle-inline input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .tt-ed__toggle-label {
                font-weight: 600;
                font-size: 0.9rem;
            }

            /* Toggle */
            .tt-ed__toggle {
                display: inline-flex;
                align-items: center;
                cursor: pointer;
                margin-top: 4px;
            }
            .tt-ed__toggle input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .tt-ed__toggle-track {
                position: relative;
                display: inline-block;
                width: 40px; height: 22px;
                background: #d1d5db;
                border-radius: 999px;
                transition: background 0.2s;
                flex-shrink: 0;
            }
            .tt-ed__toggle-thumb {
                position: absolute;
                top: 2px; left: 2px;
                width: 18px; height: 18px;
                background: #fff;
                border-radius: 50%;
                transition: transform 0.2s;
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            }
            .tt-ed__toggle input:checked ~ .tt-ed__toggle-track,
            .tt-ed__toggle-inline input:checked ~ .tt-ed__toggle-track { background: #14161f; }
            .tt-ed__toggle input:checked ~ .tt-ed__toggle-track .tt-ed__toggle-thumb,
            .tt-ed__toggle-inline input:checked ~ .tt-ed__toggle-track .tt-ed__toggle-thumb { transform: translateX(18px); }

            /* USP grid — 3 blokken naast elkaar */
            .tt-ed__usps,
            .tt-ed__features {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }
            /* Fleet grid — 3 breed voor slots 1-3, laatste rij spant */
            .tt-ed__fleet-list,
            .tt-ed__reviews-list,
            .tt-ed__faq-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }
            @media (max-width: 1100px) {
                .tt-ed__fleet-list,
                .tt-ed__reviews-list,
                .tt-ed__faq-grid { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 700px) {
                .tt-ed__fleet-list,
                .tt-ed__reviews-list,
                .tt-ed__faq-grid { grid-template-columns: 1fr; }
            }
            /* Readonly home-FAQ items in de FAQ-page editor — visueel onderscheiden */
            .tt-ed__faq-row--readonly {
                background: #f9fafb;
                border-color: #e5e7eb;
                opacity: 0.85;
            }
            .tt-ed__faq-num--home {
                background: #6b7280 !important;
            }
            .tt-ed__faq-row--readonly input,
            .tt-ed__faq-row--readonly textarea {
                background: #f3f4f6 !important;
                color: #6b7280 !important;
                cursor: not-allowed;
            }
            .tt-ed__usp-row {
                display: flex;
                flex-direction: column;
                gap: 14px;
                padding: 18px;
                background: #fdfcf7;
                border: 1px solid #f0ede2;
                border-radius: 10px;
            }
            .tt-ed__usp-num {
                background: #14161f;
                color: #fff;
                padding: 4px 10px;
                border-radius: 5px;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.03em;
                white-space: nowrap;
                align-self: flex-start;
            }
            .tt-ed__usp-grid {
                display: flex;
                flex-direction: column;
                gap: 12px;
                flex: 1;
            }
            /* Kleinere icon-tegels binnen smallere USP-cards */
            .tt-ed__usps .tt-ed__icon-picker {
                grid-template-columns: repeat(auto-fill, minmax(38px, 1fr));
                gap: 6px;
            }

            /* Extra info-blokken — 3 kolommen naast elkaar met image picker per blok */
            .tt-ed__page-sections--grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
            }
            .tt-ed__page-section-card {
                display: flex;
                flex-direction: column;
                gap: 14px;
                padding: 20px;
                background: #fdfcf7;
                border: 1px solid #f0ede2;
                border-radius: 10px;
            }
            .tt-ed__page-section-card__num {
                background: #14161f;
                color: #fff;
                padding: 4px 10px;
                border-radius: 5px;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.03em;
                align-self: flex-start;
            }
            /* Image preview binnen page-section-card wat compacter */
            .tt-ed__page-section-card .tt-ed__image-preview {
                min-height: 100px;
                padding: 10px;
            }

            /* Tarieven-editor: ruime kaarten op desktop, logisch gestapeld op mobiel. */
            .tt-ed__tariff-intro {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px 24px;
                align-items: start;
            }
            .tt-ed__field-stack { display: flex; flex-direction: column; gap: 14px; }
            .tt-ed__tariff-destinations {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
                margin-top: 20px;
            }
            .tt-ed__tariff-destination,
            .tt-ed__tariff-zone {
                min-width: 0;
                background: #fdfcf7;
                border: 1px solid #f0ede2;
                border-radius: 10px;
            }
            .tt-ed__tariff-destination {
                display: flex;
                flex-direction: column;
                gap: 6px;
                padding: 12px;
            }
            .tt-ed__tariff-destination label {
                color: #6b7280;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }
            .tt-ed__tariff-destination input,
            .tt-ed__tariff-row input {
                width: 100%;
                min-width: 0;
                padding: 8px 10px;
                border: 1px solid #e6e2d5;
                border-radius: 6px;
                background: #fff;
                font: inherit;
                font-size: 0.85rem;
            }
            .tt-ed__tariff-destinations input:focus,
            .tt-ed__tariff-rows input:focus {
                border-color: #f5b800;
                box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.15);
                outline: none;
            }
            .tt-ed__tariff-zones {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
                margin-top: 16px;
            }
            .tt-ed__tariff-zone {
                display: flex;
                flex-direction: column;
                gap: 12px;
                padding: 18px;
            }
            .tt-ed__tariff-zone-label { display: flex; align-items: center; }
            .tt-ed__tariff-zone-label span {
                padding: 3px 8px;
                border-radius: 5px;
                background: #14161f;
                color: #fff;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.03em;
            }
            .tt-ed__tariff-zone-head {
                display: grid;
                grid-template-columns: 130px 1fr;
                gap: 10px;
            }
            .tt-ed__tariff-zone-head .tt-ed__field { min-width: 0; }
            .tt-ed__tariff-rows-label {
                display: block;
                margin-bottom: 6px;
                font-size: 0.85rem;
                font-weight: 600;
            }
            .tt-ed__tariff-rows {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 6px;
            }
            .tt-ed__tariff-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 76px;
                gap: 4px;
                min-width: 0;
            }
            @media (max-width: 1100px) {
                .tt-ed__page-sections--grid {
                    grid-template-columns: 1fr;
                }
                .tt-ed__tariff-destinations { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .tt-ed__tariff-zones { grid-template-columns: 1fr; }
            }
            @media (max-width: 700px) {
                .tt-ed__tariff-intro,
                .tt-ed__tariff-destinations,
                .tt-ed__tariff-rows { grid-template-columns: 1fr; }
                .tt-ed__tariff-zone-head { grid-template-columns: 1fr; }
            }

            /* ============ Home-editor layout met left sidebar ============ */
            .tt-ed__layout {
                display: grid;
                grid-template-columns: 280px minmax(0, 1fr);
                gap: 24px;
                align-items: start;
            }
            .tt-ed__sidebar {
                position: sticky;
                top: 42px;
                background: #fff;
                border: 1px solid #e6e2d5;
                border-radius: 12px;
                padding: 12px;
                max-height: calc(100vh - 60px);
                overflow-y: auto;
                box-shadow: 0 2px 8px -4px rgba(20, 22, 31, 0.06);
            }
            .tt-ed__sidebar.is-collapsed {
                width: 44px;
                padding: 8px;
            }
            .tt-ed__sidebar.is-collapsed .tt-ed__sidebar-list,
            .tt-ed__sidebar.is-collapsed .tt-ed__sidebar-head strong,
            .tt-ed__sidebar.is-collapsed .tt-ed__sidebar-section-label,
            .tt-ed__sidebar.is-collapsed .tt-ed__sidebar-static,
            .tt-ed__sidebar.is-collapsed .tt-ed__sidebar-help {
                display: none;
            }
            .tt-ed__sidebar.is-collapsed + .tt-ed__main { /* no effect, layout is grid */ }
            .tt-ed__layout:has(.tt-ed__sidebar.is-collapsed) {
                grid-template-columns: 44px 1fr;
            }
            .tt-ed__sidebar-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 6px 8px 10px;
                border-bottom: 1px solid #f0ede2;
                margin-bottom: 8px;
                font-size: 0.85rem;
                color: #14161f;
            }
            .tt-ed__sidebar-toggle {
                background: transparent;
                border: 1px solid transparent;
                color: #6b7280;
                width: 24px;
                height: 24px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 1rem;
                line-height: 1;
                padding: 0;
            }
            .tt-ed__sidebar-toggle:hover { background: #fdfcf7; border-color: #e6e2d5; }

            .tt-ed__sidebar-list {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            .tt-ed__sidebar-section-label {
                margin: 14px 8px 7px;
                color: #9ca3af;
                font-size: 0.66rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }
            .tt-ed__sidebar-static {
                display: block;
                padding: 9px 10px;
                border: 1px solid #f0ede2;
                border-radius: 8px;
                background: #fff;
            }
            .tt-ed__sidebar-static.is-active {
                color: #14161f;
                border-color: #f5b800;
                background: #fff8e5;
                box-shadow: inset 3px 0 0 #f5b800;
            }
            .tt-ed__sidebar-row {
                display: grid;
                grid-template-columns: 1fr auto auto auto;
                gap: 6px;
                align-items: center;
                padding: 8px 10px;
                border-radius: 8px;
                background: #fdfcf7;
                border: 1px solid #f0ede2;
                opacity: 0.65;
                transition: opacity 0.15s, background 0.15s;
            }
            .tt-ed__sidebar-row.is-enabled { opacity: 1; }
            .tt-ed__sidebar-row:hover { background: #fff8e5; }
            .tt-ed__sidebar-row.is-active-component {
                background: #fff8e5;
                border-color: #f5b800;
                box-shadow: inset 3px 0 0 #f5b800;
            }
            .tt-ed__sidebar-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 22px;
                height: 22px;
                background: #fff;
                border: 1px solid #d5cfbb;
                border-radius: 4px;
                font-size: 0.8rem;
                font-weight: 700;
                color: #14161f;
                cursor: pointer;
                padding: 0;
                line-height: 1;
                transition: background 0.12s, border-color 0.12s;
            }
            .tt-ed__sidebar-btn:hover {
                background: #f5b800;
                border-color: #f5b800;
            }
            .tt-ed__sidebar-switch {
                background: transparent;
                border: none;
                padding: 0;
                cursor: pointer;
            }
            .tt-ed__sidebar-native-toggle {
                position: absolute;
                width: 1px;
                height: 1px;
                opacity: 0;
                pointer-events: none;
            }
            .tt-ed__sidebar-switch-track {
                display: inline-block;
                width: 26px;
                height: 14px;
                background: #d1d5db;
                border-radius: 999px;
                position: relative;
                transition: background 0.15s;
            }
            .tt-ed__sidebar-switch-thumb {
                position: absolute;
                top: 2px; left: 2px;
                width: 10px; height: 10px;
                background: #fff;
                border-radius: 50%;
                transition: transform 0.15s;
            }
            .tt-ed__sidebar-switch.is-on .tt-ed__sidebar-switch-track { background: #14161f; }
            .tt-ed__sidebar-switch.is-on .tt-ed__sidebar-switch-thumb { transform: translateX(12px); }
            .tt-ed__sidebar-switch-placeholder {
                display: inline-block;
                width: 26px;
                height: 14px;
            }
            .tt-ed__sidebar-jump {
                color: #14161f;
                text-decoration: none;
                font-size: 0.85rem;
                font-weight: 600;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .tt-ed__sidebar-jump:hover { color: #c17d00; }
            .tt-ed__sidebar-jump.is-active { color: #14161f; }
            .tt-ed__sidebar-help {
                margin: 10px 4px 2px;
                color: #8a8f99;
                font-size: 0.68rem;
                line-height: 1.5;
            }

            /* Visual feedback bij verplaatsen */
            .tt-ed__group--moved {
                animation: tt-ed-move-flash 0.5s ease;
            }
            @keyframes tt-ed-move-flash {
                0%   { box-shadow: 0 0 0 3px rgba(245, 184, 0, 0.5); }
                100% { box-shadow: 0 0 0 0 rgba(245, 184, 0, 0); }
            }

            /* Mobile: sidebar boven de content (stacked) */
            @media (max-width: 900px) {
                .tt-ed__layout {
                    grid-template-columns: 1fr;
                }
                .tt-ed__sidebar {
                    position: static;
                    max-height: none;
                }
            }

            /* Actions card */
            .tt-ed__actions {
                background: #fff;
                border: 1px solid #e6e2d5;
                border-radius: 14px;
                padding: 20px 28px;
                margin-top: 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                box-shadow: 0 8px 24px -8px rgba(20, 22, 31, 0.18);
                position: sticky;
                bottom: 20px;
                transition: opacity 0.25s ease, transform 0.25s ease;
            }
            /* Verborgen (initieel wanneer form niet dirty) */
            .tt-ed__actions--hidden {
                opacity: 0;
                transform: translateY(20px);
                pointer-events: none;
            }
            .tt-ed__btn {
                display: inline-block;
                padding: 12px 22px;
                border-radius: 8px;
                font-family: inherit;
                font-weight: 700;
                font-size: 0.9rem;
                cursor: pointer;
                border: 1.5px solid transparent;
                text-decoration: none;
                transition: transform 0.15s, box-shadow 0.15s;
            }
            .tt-ed__btn--primary {
                background: #f5b800;
                color: #14161f;
                box-shadow: 0 4px 12px -3px rgba(245, 184, 0, 0.5);
            }
            .tt-ed__btn--primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 18px -3px rgba(245, 184, 0, 0.6);
                color: #14161f;
            }
            .tt-ed__btn--ghost {
                background: transparent;
                color: #6b7280;
                border-color: #e6e2d5;
            }
            .tt-ed__btn--ghost:hover { color: #14161f; border-color: #14161f; }

            @media (max-width: 900px) {
                .tt-ed { padding: 20px 16px; }
                .tt-ed__topbar { grid-template-columns: 1fr; text-align: left; gap: 12px; }
                .tt-ed__back, .tt-ed__view { justify-self: start; }
                .tt-ed__title-block { text-align: left; }
                .tt-ed__subtitle { margin-left: 0; }
                .tt-ed__grid { grid-template-columns: 1fr; }
                .tt-ed__usps,
                .tt-ed__features { grid-template-columns: 1fr; }
                .tt-ed__actions { flex-direction: column; align-items: stretch; }
            }
        </style>
        <?php
    }
}
