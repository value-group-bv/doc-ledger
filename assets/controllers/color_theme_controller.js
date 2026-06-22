import { Controller } from "@hotwired/stimulus"

const THEME_IDS = { system: 'theme-system', light: 'theme-light', dark: 'theme-dark' }

export default class extends Controller {
  connect() {
    this.applyTheme(localStorage.getItem('theme') ?? 'system')
  }

  setSystem() { this.applyTheme('system'); localStorage.setItem('theme', 'system') }
  setLight()  { this.applyTheme('light');  localStorage.setItem('theme', 'light') }
  setDark()   { this.applyTheme('dark');   localStorage.setItem('theme', 'dark') }

  applyTheme(theme) {
    if (theme === 'dark') {
      document.documentElement.classList.add('dark')
    } else if (theme === 'light') {
      document.documentElement.classList.remove('dark')
    } else {
      document.documentElement.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches)
    }

    for (const [t, id] of Object.entries(THEME_IDS)) {
      const btn = document.getElementById(id)
      if (!btn) continue
      if (t === theme) {
        btn.setAttribute('aria-pressed', 'true')
      } else {
        btn.removeAttribute('aria-pressed')
      }
    }
  }
}
