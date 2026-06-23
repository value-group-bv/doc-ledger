import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static targets = ["menu", "menuButton"]

  toggleMenu() {
    const open = this.menuButtonTarget.getAttribute('aria-expanded') === 'true'
    this.menuButtonTarget.setAttribute('aria-expanded', String(!open))
    this.menuTarget.classList.toggle('hidden', open)
  }
}
