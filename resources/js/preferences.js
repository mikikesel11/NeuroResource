/*
 | NeuroResource — Accessibility preferences (client side).
 |
 | Applies and persists the user's display preferences (theme, text size, line
 | height, letter spacing, reduced motion) to <html> as data-* attributes and
 | CSS custom properties. Persisted to localStorage immediately for a flicker-
 | free experience; logged-in users can additionally sync server-side via the
 | preferences endpoint (wired in a later increment). See design §3.5.
 |
 | The matching read-before-paint snippet lives in the layout <head> so the
 | correct theme is applied before first paint (no flash of default theme).
 */

const STORAGE_KEY = 'ns:preferences';

const THEME_LABELS = {
  light: 'Light',
  dark: 'Dark',
  'high-contrast': 'High Contrast',
  'low-stimulation': 'Low Stimulation',
};

const DEFAULTS = {
  theme: null, // null => follow OS (prefers-color-scheme)
  textScale: 1,
  lineHeight: 1.6,
  letterSpacing: 0,
  reduceMotion: null, // null => follow OS (prefers-reduced-motion)
};

export function loadPreferences() {
  try {
    return { ...DEFAULTS, ...JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') };
  } catch {
    return { ...DEFAULTS };
  }
}

export function applyPreferences(prefs = loadPreferences()) {
  const root = document.documentElement;

  const theme =
    prefs.theme ??
    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  root.setAttribute('data-theme', theme);

  const reduceMotion =
    prefs.reduceMotion ??
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  root.setAttribute('data-reduce-motion', reduceMotion ? 'true' : 'false');

  root.style.setProperty('--ns-text-scale', String(prefs.textScale));
  root.style.setProperty('--ns-line-height', String(prefs.lineHeight));
  root.style.setProperty('--ns-letter-spacing', `${prefs.letterSpacing}em`);

  // Reflect the active selections back into the widget (if present on the page).
  syncWidget(prefs, theme, reduceMotion);
}

/**
 * Mirror the current preferences into the Display & Accessibility widget so the
 * user can see what is selected. Sets aria-pressed for assistive tech and lets
 * CSS add a visible checkmark (a non-color indicator, per WCAG 1.4.1).
 */
function syncWidget(prefs, effectiveTheme, effectiveMotion) {
  if (typeof document === 'undefined') return;

  document.querySelectorAll('[data-ns-theme]').forEach((btn) => {
    btn.setAttribute('aria-pressed', prefs.theme === btn.dataset.nsTheme ? 'true' : 'false');
  });

  document.querySelectorAll('[data-ns-action="follow-system"]').forEach((btn) => {
    btn.setAttribute('aria-pressed', prefs.theme == null ? 'true' : 'false');
  });

  document.querySelectorAll('[data-ns-effective-theme]').forEach((el) => {
    el.textContent = THEME_LABELS[effectiveTheme] ?? effectiveTheme;
  });

  document.querySelectorAll('[data-ns-motion]').forEach((btn) => {
    btn.setAttribute('aria-pressed', effectiveMotion ? 'true' : 'false');
  });

  document.querySelectorAll('[data-ns-text-display]').forEach((el) => {
    el.textContent = `${Math.round(prefs.textScale * 100)}%`;
  });
}

export function savePreferences(update) {
  const next = { ...loadPreferences(), ...update };
  localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  applyPreferences(next);
  return next;
}

// Convenience API exposed to the accessibility widget (see public-layout).
const TEXT_MIN = 0.85;
const TEXT_MAX = 1.6;

window.nsPrefs = {
  load: loadPreferences,
  apply: applyPreferences,
  save: savePreferences,
  setTheme: (theme) => savePreferences({ theme }), // pass null to follow OS
  toggleMotion: () => savePreferences({ reduceMotion: !loadPreferences().reduceMotion }),
  adjustText: (delta) => {
    const next = +(loadPreferences().textScale + delta).toFixed(2);
    return savePreferences({ textScale: Math.min(TEXT_MAX, Math.max(TEXT_MIN, next)) });
  },
  reset: () => {
    localStorage.removeItem('ns:preferences');
    applyPreferences();
  },
};

// Apply as early as possible on load.
applyPreferences();

// Re-apply after Livewire SPA navigation swaps in a fresh widget instance.
document.addEventListener('livewire:navigated', () => applyPreferences());
