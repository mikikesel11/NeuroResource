/*
 | NeuroResource — Adventure engine.
 |
 | A small, framework-agnostic runtime for a branching, click-through story.
 | Reads a JSON scene graph (see resources/adventure/story.json and
 | docs/adventure-authoring.md) and renders it accessibly:
 |
 | - Choices are real <button>s (full keyboard support).
 | - On each scene change, focus moves to the scene heading and the title is
 |   announced via a polite live region.
 | - Progress (current scene + history) is saved to localStorage; "Go Back" and
 |   "Start Over" are always available. No timers, no autoplay, no wrong choices.
 |
 | Designed to be lifted to a static play.neuroresource.org build later — it has no
 | server dependency beyond the story JSON embedded in the page.
 */

const STORAGE_PREFIX = 'ns:adventure:';

function boot() {
    const root = document.getElementById('adventure');
    const dataEl = document.getElementById('adventure-data');
    if (!root || !dataEl) return;

    let story;
    try {
        story = JSON.parse(dataEl.textContent);
    } catch {
        root.innerHTML = '<p>Sorry — this Adventure could not be loaded.</p>';
        return;
    }

    new Adventure(root, story).start();
}

class Adventure {
    constructor(root, story) {
        this.root = root;
        this.story = story;
        this.scenes = story.scenes || {};
        this.storyId = root.dataset.storyId || story.title || 'story';
        this.storageKey = STORAGE_PREFIX + this.storyId;

        // Cross-device save for logged-in players (data-* set by the play page).
        this.authenticated = root.dataset.authenticated === 'true';
        this.progressUrl = root.dataset.progressUrl || null;
        this.csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async start() {
        let saved = null;

        if (this.authenticated && this.progressUrl) {
            saved = await this.loadRemote();
            if (!saved) {
                // No account save yet — migrate any anonymous local progress up.
                const local = this.loadLocal();
                if (local?.scene && this.scenes[local.scene]) {
                    saved = local;
                    this.saveRemote(local.scene, local.history || []);
                }
            }
        }

        if (!saved) saved = this.loadLocal();

        this.current = saved?.scene && this.scenes[saved.scene] ? saved.scene : this.story.start;
        this.history = Array.isArray(saved?.history) ? saved.history : [];
        this.render();
    }

    go(sceneId) {
        if (!this.scenes[sceneId]) return;
        this.history.push(this.current);
        this.current = sceneId;
        this.save();
        this.render();
    }

    back() {
        if (this.history.length === 0) return;
        this.current = this.history.pop();
        this.save();
        this.render();
    }

    restart() {
        this.current = this.story.start;
        this.history = [];
        this.save();
        this.render();
    }

    render() {
        const scene = this.scenes[this.current];
        if (!scene) return;

        const isEnding = scene.ending === true || !(scene.choices && scene.choices.length);

        this.root.replaceChildren();

        // Polite live region announces the new scene for screen readers.
        const announcer = el('p', { class: 'sr-only', 'aria-live': 'polite' });

        const heading = el('h2', {
            class: 'adventure-scene-title',
            tabindex: '-1',
            id: 'adventure-scene-title',
        }, scene.title || '');

        const body = el('div', { class: 'ns-prose adventure-text' });
        (scene.text || []).forEach((para) => body.append(el('p', {}, para)));

        const actions = el('div', { class: 'adventure-actions' });

        if (isEnding) {
            const end = el('p', { class: 'adventure-ending' }, 'The End — for now.');
            body.append(end);
        } else {
            const list = el('ul', { class: 'adventure-choices' });
            scene.choices.forEach((choice) => {
                const btn = el('button', { type: 'button', class: 'adventure-choice' }, choice.label);
                btn.addEventListener('click', () => this.go(choice.to));
                const item = el('li');
                item.append(btn);
                list.append(item);
            });
            actions.append(list);
        }

        // Comfort controls — present everywhere, gentle by design.
        const controls = el('div', { class: 'adventure-controls' });
        if (this.history.length > 0) {
            const backBtn = el('button', { type: 'button', class: 'adventure-control' }, '← Go Back');
            backBtn.addEventListener('click', () => this.back());
            controls.append(backBtn);
        }
        if (this.current !== this.story.start || this.history.length > 0) {
            const restartBtn = el('button', { type: 'button', class: 'adventure-control' }, 'Start Over');
            restartBtn.addEventListener('click', () => this.restart());
            controls.append(restartBtn);
        }

        this.root.append(announcer, heading, body, actions, controls);

        // Move focus to the heading and announce the scene title.
        heading.focus();
        announcer.textContent = scene.title || '';
    }

    save() {
        this.saveLocal();
        this.saveRemote(this.current, this.history);
    }

    loadLocal() {
        try {
            return JSON.parse(localStorage.getItem(this.storageKey) || 'null');
        } catch {
            return null;
        }
    }

    saveLocal() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify({ scene: this.current, history: this.history }));
        } catch {
            /* storage unavailable — play continues without save */
        }
    }

    async loadRemote() {
        try {
            const res = await fetch(`${this.progressUrl}?story_id=${encodeURIComponent(this.storyId)}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) return null;
            const data = await res.json();
            return data && data.scene ? data : null;
        } catch {
            return null;
        }
    }

    saveRemote(scene, history) {
        if (!this.authenticated || !this.progressUrl) return;
        fetch(this.progressUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': this.csrf,
            },
            body: JSON.stringify({ story_id: this.storyId, scene_id: scene, state: { history } }),
        }).catch(() => {
            /* offline or session expired — local save still holds */
        });
    }
}

function el(tag, attrs = {}, text) {
    const node = document.createElement(tag);
    for (const [key, value] of Object.entries(attrs)) node.setAttribute(key, value);
    if (text != null) node.textContent = text;
    return node;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

// Re-boot after Livewire SPA navigation lands on the play page.
document.addEventListener('livewire:navigated', boot);
