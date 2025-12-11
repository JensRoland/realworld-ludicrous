/**
 * Tag Input Web Component
 * A chip-style tag input with autocomplete for article creation/editing.
 */
class TagInput extends HTMLElement {
  constructor() {
    super();
    this.tags = [];
    this.suggestions = [];
    this.highlightedIndex = -1;
    this.abortController = null;
  }

  connectedCallback() {
    this.name = this.getAttribute('name') || 'tags';
    const initialTags = this.getAttribute('value') || '';
    this.tags = initialTags ? initialTags.split(',').map(t => t.trim()).filter(Boolean) : [];

    this.render();
    this.setupEventListeners();
  }

  render() {
    this.innerHTML = `
      <div class="tag-input-container">
        <div class="tag-input-tags">
          ${this.tags.map(tag => this.renderTag(tag)).join('')}
          <input type="text" class="tag-input-field" placeholder="${this.tags.length ? '' : 'Enter tags'}" autocomplete="off">
        </div>
        <input type="hidden" name="${this.name}" value="${this.tags.join(',')}">
        <ul class="tag-input-suggestions"></ul>
      </div>
    `;

    this.input = this.querySelector('.tag-input-field');
    this.hiddenInput = this.querySelector('input[type="hidden"]');
    this.suggestionsEl = this.querySelector('.tag-input-suggestions');
    this.tagsContainer = this.querySelector('.tag-input-tags');
  }

  renderTag(tag) {
    return `
      <span class="tag-input-tag" data-tag="${this.escapeHtml(tag)}">
        <span class="tag-input-tag-text">${this.escapeHtml(tag)}</span>
        <button type="button" class="tag-input-tag-remove" aria-label="Remove ${this.escapeHtml(tag)}">&times;</button>
      </span>
    `;
  }

  escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  setupEventListeners() {
    // Input events
    this.input.addEventListener('input', () => this.onInput());
    this.input.addEventListener('keydown', (e) => this.onKeydown(e));
    this.input.addEventListener('focus', () => this.onFocus());
    this.input.addEventListener('blur', () => this.onBlur());

    // Click on container focuses input
    this.tagsContainer.addEventListener('click', (e) => {
      if (e.target === this.tagsContainer || e.target.classList.contains('tag-input-tags')) {
        this.input.focus();
      }
    });

    // Tag removal via delegation
    this.tagsContainer.addEventListener('click', (e) => {
      const removeBtn = e.target.closest('.tag-input-tag-remove');
      if (removeBtn) {
        const tagEl = removeBtn.closest('.tag-input-tag');
        const tag = tagEl.dataset.tag;
        this.removeTag(tag);
      }
    });

    // Suggestion click via delegation
    this.suggestionsEl.addEventListener('mousedown', (e) => {
      e.preventDefault(); // Prevent blur
      const li = e.target.closest('li');
      if (li && li.dataset.tag) {
        this.addTag(li.dataset.tag);
      }
    });
  }

  onInput() {
    const query = this.input.value.trim();
    if (query.length > 0) {
      this.fetchSuggestions(query);
    } else {
      this.hideSuggestions();
    }
  }

  onFocus() {
    const query = this.input.value.trim();
    if (query.length > 0 && this.suggestions.length > 0) {
      this.showSuggestions();
    }
  }

  onBlur() {
    // Delay to allow click events on suggestions
    setTimeout(() => this.hideSuggestions(), 150);
  }

  onKeydown(e) {
    switch (e.key) {
      case 'Enter':
        e.preventDefault();
        if (this.highlightedIndex >= 0 && this.suggestions[this.highlightedIndex]) {
          this.addTag(this.suggestions[this.highlightedIndex]);
        } else if (this.input.value.trim()) {
          this.addTag(this.input.value.trim());
        }
        break;

      case ',':
        e.preventDefault();
        if (this.input.value.trim()) {
          this.addTag(this.input.value.trim());
        }
        break;

      case 'Backspace':
        if (!this.input.value && this.tags.length > 0) {
          this.removeTag(this.tags[this.tags.length - 1]);
        }
        break;

      case 'Escape':
        this.hideSuggestions();
        this.input.blur();
        break;

      case 'ArrowDown':
        e.preventDefault();
        this.highlightNext();
        break;

      case 'ArrowUp':
        e.preventDefault();
        this.highlightPrev();
        break;

      case 'Tab':
        if (this.highlightedIndex >= 0 && this.suggestions[this.highlightedIndex]) {
          e.preventDefault();
          this.addTag(this.suggestions[this.highlightedIndex]);
        }
        break;
    }
  }

  async fetchSuggestions(query) {
    // Cancel previous request
    if (this.abortController) {
      this.abortController.abort();
    }
    this.abortController = new AbortController();

    try {
      const response = await fetch(`/api/tags?q=${encodeURIComponent(query)}`, {
        signal: this.abortController.signal
      });
      const allTags = await response.json();

      // Filter out already selected tags
      this.suggestions = allTags.filter(tag => !this.tags.includes(tag));
      this.highlightedIndex = -1;

      if (this.suggestions.length > 0) {
        this.renderSuggestions(query);
        this.showSuggestions();
      } else {
        this.hideSuggestions();
      }
    } catch (err) {
      if (err.name !== 'AbortError') {
        console.error('Failed to fetch tag suggestions:', err);
      }
    }
  }

  renderSuggestions(query) {
    const queryLower = query.toLowerCase();
    this.suggestionsEl.innerHTML = this.suggestions
      .slice(0, 8) // Limit to 8 suggestions
      .map((tag, i) => {
        // Highlight matching part
        const idx = tag.toLowerCase().indexOf(queryLower);
        let html;
        if (idx >= 0) {
          html = this.escapeHtml(tag.slice(0, idx)) +
                 '<strong>' + this.escapeHtml(tag.slice(idx, idx + query.length)) + '</strong>' +
                 this.escapeHtml(tag.slice(idx + query.length));
        } else {
          html = this.escapeHtml(tag);
        }
        return `<li data-tag="${this.escapeHtml(tag)}" class="${i === this.highlightedIndex ? 'highlighted' : ''}">${html}</li>`;
      })
      .join('');
  }

  showSuggestions() {
    this.suggestionsEl.classList.add('visible');
  }

  hideSuggestions() {
    this.suggestionsEl.classList.remove('visible');
    this.highlightedIndex = -1;
  }

  highlightNext() {
    if (this.suggestions.length === 0) return;
    this.highlightedIndex = Math.min(this.highlightedIndex + 1, Math.min(this.suggestions.length - 1, 7));
    this.updateHighlight();
  }

  highlightPrev() {
    if (this.suggestions.length === 0) return;
    this.highlightedIndex = Math.max(this.highlightedIndex - 1, -1);
    this.updateHighlight();
  }

  updateHighlight() {
    const items = this.suggestionsEl.querySelectorAll('li');
    items.forEach((li, i) => {
      li.classList.toggle('highlighted', i === this.highlightedIndex);
    });
    // Scroll highlighted item into view
    if (this.highlightedIndex >= 0 && items[this.highlightedIndex]) {
      items[this.highlightedIndex].scrollIntoView({ block: 'nearest' });
    }
  }

  addTag(tag) {
    const normalized = tag.trim().toLowerCase();
    if (!normalized || this.tags.includes(normalized)) {
      this.input.value = '';
      this.hideSuggestions();
      return;
    }

    this.tags.push(normalized);
    this.updateUI();
    this.input.value = '';
    this.hideSuggestions();
    this.input.focus();
  }

  removeTag(tag) {
    this.tags = this.tags.filter(t => t !== tag);
    this.updateUI();
    this.input.focus();
  }

  updateUI() {
    // Update hidden input
    this.hiddenInput.value = this.tags.join(',');

    // Re-render tags
    const tagElements = this.tags.map(tag => this.renderTag(tag)).join('');
    this.tagsContainer.innerHTML = tagElements + `<input type="text" class="tag-input-field" placeholder="${this.tags.length ? '' : 'Enter tags'}" autocomplete="off">`;

    // Re-acquire input reference and re-attach listeners
    this.input = this.querySelector('.tag-input-field');
    this.input.addEventListener('input', () => this.onInput());
    this.input.addEventListener('keydown', (e) => this.onKeydown(e));
    this.input.addEventListener('focus', () => this.onFocus());
    this.input.addEventListener('blur', () => this.onBlur());

    // Update placeholder
    this.input.placeholder = this.tags.length ? '' : 'Enter tags';
  }
}

customElements.define('tag-input', TagInput);
